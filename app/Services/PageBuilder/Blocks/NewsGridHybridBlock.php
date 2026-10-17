<?php

namespace App\Services\PageBuilder\Blocks;

use App\Enums\MediaCenterMode;
use App\Services\MediaCenter\MediaCenterApiService;
use App\Services\News\HybridNewsAggregator;
use App\Services\News\NewsItem;
use Illuminate\Validation\Rule;

/**
 * Grid berita gabungan: artikel lokal dinas + (opsional) berita Media Center.
 *
 * Contoh props tersimpan:
 * {"title": "Berita Sosial", "limit": 6, "columns": 3, "local_category": "sosial",
 *  "fetch_media_center": true, "media_center_keyword": "sosial", "media_center_mode": "tag"}
 */
class NewsGridHybridBlock extends AbstractBlock
{
    public function __construct(private HybridNewsAggregator $news) {}

    public function type(): string
    {
        return 'NewsGridHybrid';
    }

    public function label(): string
    {
        return 'Grid Berita (Lokal + Media Center)';
    }

    public function description(): string
    {
        return 'Berita terbaru dinas, bisa digabung dengan berita Media Center Kota berdasarkan kata kunci.';
    }

    public function defaults(): array
    {
        return [
            'title' => 'Berita Terkini',
            'limit' => 6,
            'columns' => 3,
            'local_category' => null,
            'fetch_media_center' => false,
            'media_center_keyword' => null,
            'media_center_mode' => MediaCenterMode::Tag->value,
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'limit' => ['required', 'integer', 'between:1,12'],
            'columns' => ['required', 'integer', Rule::in([2, 3, 4])],
            'local_category' => ['nullable', 'string', Rule::exists('categories', 'slug')],
            'fetch_media_center' => ['required', 'boolean'],
            'media_center_keyword' => ['nullable', 'required_if_accepted:fetch_media_center', 'string', 'regex:'.MediaCenterApiService::KEYWORD_PATTERN],
            'media_center_mode' => ['required', Rule::enum(MediaCenterMode::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'media_center_keyword.regex' => 'Kata kunci hanya boleh huruf kecil, angka, dan tanda hubung (maks. 50 karakter).',
            'media_center_keyword.required_if_accepted' => 'Isi kata kunci Media Center, mis. "sosial".',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul blok',
            'limit' => 'jumlah berita',
            'columns' => 'jumlah kolom',
            'local_category' => 'kategori lokal',
            'media_center_keyword' => 'kata kunci Media Center',
            'media_center_mode' => 'mode pencarian Media Center',
        ];
    }

    public function normalize(array $props): array
    {
        $props = parent::normalize($props);

        return [
            'title' => filled($props['title']) ? (string) $props['title'] : null,
            'limit' => (int) $props['limit'],
            'columns' => (int) $props['columns'],
            'local_category' => filled($props['local_category']) ? (string) $props['local_category'] : null,
            'fetch_media_center' => filter_var($props['fetch_media_center'], FILTER_VALIDATE_BOOLEAN),
            'media_center_keyword' => filled($props['media_center_keyword']) ? (string) $props['media_center_keyword'] : null,
            'media_center_mode' => (string) $props['media_center_mode'],
        ];
    }

    public function hydrate(array $props): ?array
    {
        $props = $this->normalize($props);

        $items = $this->news->latest(
            limit: $props['limit'],
            localCategorySlug: $props['local_category'],
            includeMediaCenter: $props['fetch_media_center'],
            mediaCenterKeyword: $props['media_center_keyword'],
            mediaCenterMode: MediaCenterMode::tryFrom($props['media_center_mode']) ?? MediaCenterMode::Tag,
        );

        return ['items' => array_map(fn (NewsItem $item) => $item->toArray(), $items)];
    }
}
