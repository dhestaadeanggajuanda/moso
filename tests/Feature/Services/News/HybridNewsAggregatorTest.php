<?php

use App\Enums\MediaCenterMode;
use App\Models\Article;
use App\Models\Category;
use App\Services\News\HybridNewsAggregator;
use App\Services\News\NewsItem;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeMediaCenterHost();
});

it('merges local and media center news by date and keeps the local copy of duplicates', function () {
    Article::factory()->published()->create(['title' => 'Penyaluran Bansos Tahap II', 'published_at' => '2026-10-14 08:00:00']);
    Article::factory()->published()->create(['title' => 'Rapat Koordinasi', 'published_at' => '2026-10-10 08:00:00']);

    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::response([
        wpPost(1, 'Penyaluran  Bansos Tahap II', '2026-10-15T01:00:00'),
        wpPost(2, 'Pasar Murah', '2026-10-12T01:00:00'),
    ])]);

    $items = app(HybridNewsAggregator::class)->latest(
        limit: 3,
        includeMediaCenter: true,
        mediaCenterKeyword: 'sosial',
        mediaCenterMode: MediaCenterMode::Search,
    );

    expect(array_map(fn (NewsItem $item) => [$item->source, $item->title], $items))->toBe([
        ['local', 'Penyaluran Bansos Tahap II'],
        ['media_center', 'Pasar Murah'],
        ['local', 'Rapat Koordinasi'],
    ]);
});

it('filters local articles by category slug', function () {
    $sosial = Category::factory()->create(['name' => 'Sosial']);
    Article::factory()->published()->create(['title' => 'Berita Sosial'])->categories()->attach($sosial);
    Article::factory()->published()->create(['title' => 'Berita Lain']);

    $items = app(HybridNewsAggregator::class)->latest(limit: 5, localCategorySlug: 'sosial');

    expect(array_map(fn (NewsItem $item) => $item->title, $items))->toBe(['Berita Sosial']);
});

it('does not call the media center when the block does not ask for it', function () {
    Http::fake();
    Article::factory()->published()->create();

    app(HybridNewsAggregator::class)->latest(limit: 5, includeMediaCenter: false, mediaCenterKeyword: 'sosial');

    Http::assertNothingSent();
});

it('falls back to local news when the media center is unreachable', function () {
    Article::factory()->published()->create(['title' => 'Berita Lokal']);
    Http::fake(['mc.test/*' => Http::failedConnection()]);

    $items = app(HybridNewsAggregator::class)->latest(
        limit: 5,
        includeMediaCenter: true,
        mediaCenterKeyword: 'sosial',
        mediaCenterMode: MediaCenterMode::Search,
    );

    expect(array_map(fn (NewsItem $item) => $item->title, $items))->toBe(['Berita Lokal']);
});
