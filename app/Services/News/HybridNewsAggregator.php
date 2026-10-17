<?php

namespace App\Services\News;

use App\Enums\MediaCenterMode;
use App\Models\Article;
use App\Services\MediaCenter\MediaCenterApiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Menggabungkan artikel lokal dinas dengan berita Media Center menjadi satu
 * daftar terurut. Bila Media Center gagal, hasilnya tetap berisi berita lokal.
 */
class HybridNewsAggregator
{
    public function __construct(private MediaCenterApiService $mediaCenter) {}

    /**
     * @return list<NewsItem>
     */
    public function latest(
        int $limit,
        ?string $localCategorySlug = null,
        bool $includeMediaCenter = false,
        ?string $mediaCenterKeyword = null,
        MediaCenterMode $mediaCenterMode = MediaCenterMode::Tag,
    ): array {
        $local = Article::query()
            ->published()
            ->when($localCategorySlug, fn (Builder $query, string $slug) => $query
                ->whereHas('categories', fn (Builder $categories) => $categories->where('slug', $slug)))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Article $article) => NewsItem::fromArticle($article))
            ->all();

        $external = $includeMediaCenter && $mediaCenterKeyword
            ? $this->mediaCenter->fetch($mediaCenterKeyword, $mediaCenterMode, $limit)
            : [];

        return array_values(collect([...$local, ...$external])
            // Berita yang sama (judul identik) cukup tampil sekali; versi lokal didahulukan.
            ->unique(fn (NewsItem $item) => Str::lower(Str::squish(Str::ascii($item->title))))
            ->sortByDesc(fn (NewsItem $item) => $item->publishedAt?->getTimestamp() ?? 0)
            ->take($limit)
            ->all());
    }
}
