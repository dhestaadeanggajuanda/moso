<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ArticleAiDraftFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Draf asisten AI yang diminta seorang pengguna.
 *
 * Tidak ada kolom fillable: pemilik diisi dari pengguna yang login, `remote_id`
 * dari jawaban agentic, dan artikel hanya ditautkan oleh ArticleController
 * setelah kepemilikan dicek.
 *
 * @property string $id
 * @property int $user_id
 * @property int|null $article_id
 * @property string $remote_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Article|null $article
 */
class ArticleAiDraft extends Model
{
    /** @use HasFactory<ArticleAiDraftFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
