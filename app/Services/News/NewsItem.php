<?php

namespace App\Services\News;

use App\Models\Article;
use Carbon\CarbonImmutable;

/**
 * Berita ternormalisasi dari sumber mana pun (artikel lokal atau Media Center).
 *
 * Semua teks sudah berupa teks polos (tanpa HTML), jadi aman dirender dengan
 * interpolasi biasa di frontend dan tidak pernah butuh v-html.
 */
final readonly class NewsItem
{
    public const SOURCE_LOCAL = 'local';

    public const SOURCE_MEDIA_CENTER = 'media_center';

    public function __construct(
        public string $id,
        public string $source,
        public string $title,
        public ?string $excerpt,
        public string $url,
        public ?string $imageUrl,
        public ?string $imageAlt,
        public ?CarbonImmutable $publishedAt,
    ) {}

    public static function fromArticle(Article $article): self
    {
        return new self(
            id: 'local-'.$article->id,
            source: self::SOURCE_LOCAL,
            title: $article->title,
            excerpt: $article->excerpt,
            url: route('site.articles.show', $article->slug),
            imageUrl: $article->image_url,
            imageAlt: $article->image_alt,
            publishedAt: $article->published_at,
        );
    }

    /**
     * @param  array{id: string, source: string, title: string, excerpt: ?string, url: string, image_url: ?string, image_alt: ?string, published_at: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            source: $data['source'],
            title: $data['title'],
            excerpt: $data['excerpt'],
            url: $data['url'],
            imageUrl: $data['image_url'],
            imageAlt: $data['image_alt'],
            publishedAt: $data['published_at'] !== null ? CarbonImmutable::parse($data['published_at']) : null,
        );
    }

    /**
     * @return array{id: string, source: string, title: string, excerpt: ?string, url: string, image_url: ?string, image_alt: ?string, published_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'image_url' => $this->imageUrl,
            'image_alt' => $this->imageAlt,
            'published_at' => $this->publishedAt?->toIso8601String(),
        ];
    }
}
