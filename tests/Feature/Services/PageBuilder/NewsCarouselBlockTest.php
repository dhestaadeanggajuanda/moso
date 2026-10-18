<?php

use App\Models\Article;
use App\Services\PageBuilder\Blocks\NewsCarouselBlock;

it('serves the latest local news with a link to the news index', function () {
    Article::factory()->published()->create(['title' => 'Berita Lama', 'published_at' => now()->subDays(2)]);
    Article::factory()->published()->create(['title' => 'Berita Baru', 'published_at' => now()->subHour()]);
    Article::factory()->create(['title' => 'Masih Draf']);

    $data = app(NewsCarouselBlock::class)->hydrate(['limit' => 8, 'fetch_media_center' => false]);

    expect(array_column($data['items'], 'title'))->toBe(['Berita Baru', 'Berita Lama'])
        ->and($data['index_url'])->toBe(route('site.articles.index'));
});
