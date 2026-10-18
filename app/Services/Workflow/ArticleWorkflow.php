<?php

namespace App\Services\Workflow;

use App\Enums\ArticleAction;
use App\Enums\ArticleCommentType;
use App\Exceptions\InvalidArticleTransition;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use App\Support\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Satu-satunya jalur perubahan status artikel (draft → submitted →
 * ready_to_publish | revision_required → published → archived).
 *
 * Otorisasi (siapa boleh melakukan aksi) ada di ArticlePolicy; kelas ini
 * menjaga aturan status, mencatat riwayat, dan menulis jejak audit.
 */
class ArticleWorkflow
{
    /**
     * Jalankan aksi pada artikel. Status dibaca ulang dengan row lock supaya dua
     * reviewer yang menekan tombol bersamaan tidak sama-sama berhasil.
     *
     * @throws InvalidArticleTransition bila status artikel tidak mengizinkan aksi ini
     */
    public function apply(
        Article $article,
        ArticleAction $action,
        User $actor,
        ?string $note = null,
        ?CarbonImmutable $publishAt = null,
    ): ArticleComment {
        $note = filled($note) ? trim($note) : null;

        if ($action->requiresNote() && $note === null) {
            throw new InvalidArgumentException("Aksi \"{$action->label()}\" wajib disertai catatan.");
        }

        if ($publishAt !== null && ! $action->allowsSchedule()) {
            throw new InvalidArgumentException("Aksi \"{$action->label()}\" tidak bisa dijadwalkan.");
        }

        return DB::transaction(function () use ($article, $action, $actor, $note, $publishAt): ArticleComment {
            $locked = Article::query()->whereKey($article->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $action->allowsFrom($from)) {
                throw InvalidArticleTransition::for($action, $from);
            }

            $locked->status = $action->to();

            if ($action === ArticleAction::Publish) {
                $locked->published_at = $publishAt ?? now();
            } elseif ($action === ArticleAction::Unarchive && $locked->published_at === null) {
                $locked->published_at = now();
            }

            $locked->save();

            $entry = new ArticleComment([
                'type' => ArticleCommentType::StatusChange,
                'from_status' => $from,
                'to_status' => $locked->status,
                'body' => $note,
            ]);
            $entry->article()->associate($locked);
            $entry->user()->associate($actor);
            $entry->save();

            DB::afterCommit(fn () => AuditLog::record('article.status_changed', [
                'article_id' => $locked->id,
                'user_id' => $actor->id,
                'action' => $action->value,
                'from' => $from->value,
                'to' => $locked->status->value,
            ]));

            $article->setRawAttributes($locked->getAttributes(), true);

            return $entry;
        });
    }

    /**
     * Aksi yang boleh dilakukan pengguna pada artikel saat ini, untuk tombol di UI.
     * Status juga dicek di sini karena Super Admin melewati Policy (Gate::before).
     *
     * @return list<ArticleAction>
     */
    public function availableActions(Article $article, User $user): array
    {
        return array_values(array_filter(
            ArticleAction::cases(),
            fn (ArticleAction $action): bool => $action->allowsFrom($article->status)
                && $user->can($action->ability(), $article),
        ));
    }
}
