<?php

use App\Models\Agenda;
use App\Models\Article;
use App\Services\News\HybridNewsAggregator;
use App\Services\PageBuilder\BlockHydrator;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    fakeMediaCenterHost();
});

it('injects news and agenda data while leaving static blocks without data', function () {
    Article::factory()->published()->create(['title' => 'Berita Lokal', 'published_at' => '2026-10-01 08:00:00']);
    Agenda::factory()->create(['title' => 'Rapat Pleno', 'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHours(2)]);
    Http::fake([
        'mc.test/wp-json/wp/v2/tags*' => Http::response([['id' => 934]]),
        'mc.test/wp-json/wp/v2/posts*' => Http::response([wpPost(1, 'Berita Media Center', '2026-10-15T01:00:00')]),
    ]);

    $blocks = app(BlockHydrator::class)->hydrate([
        ['id' => (string) Str::uuid(), 'type' => 'HeroBanner', 'props' => ['heading' => 'Halo']],
        ['id' => (string) Str::uuid(), 'type' => 'NewsGridHybrid', 'props' => [
            'limit' => 5, 'columns' => 3, 'fetch_media_center' => true, 'media_center_keyword' => 'sosial', 'media_center_mode' => 'tag',
        ]],
        ['id' => (string) Str::uuid(), 'type' => 'AgendaKegiatan', 'props' => ['limit' => 3]],
        ['id' => (string) Str::uuid(), 'type' => 'BlokTakDikenal', 'props' => []],
    ]);

    expect($blocks)->toHaveCount(3)
        ->and($blocks[0]['data'])->toBeNull()
        ->and($blocks[0]['props']['variant'])->toBe('primary')
        ->and(array_column($blocks[1]['data']['items'], 'source'))->toBe(['media_center', 'local'])
        ->and(array_column($blocks[2]['data']['items'], 'title'))->toBe(['Rapat Pleno']);
});

it('isolates a failing block and reports the error', function () {
    Exceptions::fake();
    Agenda::factory()->create(['title' => 'Tetap tampil', 'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHour()]);
    $this->mock(HybridNewsAggregator::class)->shouldReceive('latest')->andThrow(new RuntimeException('database down'));

    $blocks = app(BlockHydrator::class)->hydrate([
        ['id' => (string) Str::uuid(), 'type' => 'NewsGridHybrid', 'props' => ['limit' => 3, 'columns' => 3, 'fetch_media_center' => false, 'media_center_mode' => 'tag']],
        ['id' => (string) Str::uuid(), 'type' => 'AgendaKegiatan', 'props' => ['limit' => 3]],
    ]);

    expect($blocks[0]['data'])->toBeNull()
        ->and(array_column($blocks[1]['data']['items'], 'title'))->toBe(['Tetap tampil']);
    Exceptions::assertReported(RuntimeException::class);
});
