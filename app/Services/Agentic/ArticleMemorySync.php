<?php

namespace App\Services\Agentic;

use App\Enums\ArticleStatus;
use App\Models\Article;

/**
 * Menyamakan memori agentic dengan keadaan artikel saat ini: artikel terbit
 * di-upsert, selain itu (diarsipkan, dihapus, ditarik) dihapus dari memori.
 *
 * Keputusan diambil dari status terbaru di database, bukan dari jenis event,
 * sehingga urutan job antrean yang tertukar (mis. terbit lalu arsip dalam
 * hitungan detik) tetap berakhir benar.
 */
class ArticleMemorySync
{
    public function __construct(private AgenticClient $agentic) {}

    /**
     * @return bool false bila agentic gagal dihubungi (pemanggil boleh mengulang)
     */
    public function sync(Article $article): bool
    {
        $current = Article::withTrashed()->whereKey($article->getKey())->first();

        if ($current !== null && $this->isPublished($current)) {
            return $this->upsert($current);
        }

        return $this->agentic->deleteArticle((int) $article->getKey());
    }

    /**
     * Kirim artikel terbit ke memori tanpa membaca ulang statusnya (dipakai agentic:sync-articles).
     */
    public function upsert(Article $article): bool
    {
        return $this->agentic->upsertArticle($article->id, $this->payload($article));
    }

    public function isPublished(Article $article): bool
    {
        return ! $article->trashed()
            && $article->status === ArticleStatus::Published
            && $article->published_at !== null;
    }

    /**
     * @return array{title: string, excerpt: string|null, body_html: string, published_at: string, url: string|null, draft_id: string|null}
     */
    private function payload(Article $article): array
    {
        return [
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            // Sudah disanitasi saat disimpan; agentic mengubahnya menjadi teks polos.
            'body_html' => $article->body,
            'published_at' => (string) $article->published_at?->toIso8601String(),
            'url' => route('site.articles.show', $article->slug),
            // Draf AI terakhir yang tertaut, agar agentic bisa membandingkan draf dengan hasil suntingan editor.
            'draft_id' => $article->aiDrafts()->latest('updated_at')->value('remote_id'),
        ];
    }
}
