<?php

use App\Enums\MediaCenterMode;
use App\Models\Page;
use App\Services\MediaCenter\MediaCenterApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    fakeMediaCenterHost();
});

/**
 * @return list<array{id: string, type: string, props: array<string, mixed>}>
 */
function gridBlocks(string $keyword): array
{
    return [[
        'id' => (string) Str::uuid(),
        'type' => 'NewsGridHybrid',
        'props' => [
            'title' => null, 'limit' => 6, 'columns' => 3, 'local_category' => null,
            'fetch_media_center' => true, 'media_center_keyword' => $keyword, 'media_center_mode' => 'search',
        ],
    ]];
}

it('refreshes keywords used by published pages only', function () {
    Page::factory()->published()->create(['content_blocks' => gridBlocks('sosial')]);
    Page::factory()->create(['content_blocks' => gridBlocks('kesehatan')]);
    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::response([wpPost(1, 'Berita', '2026-10-12T00:00:00')])]);

    $this->artisan('mediacenter:warm')->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request['search'] === 'sosial');
    Http::assertNotSent(fn (Request $request) => $request['search'] === 'kesehatan');
});

it('keeps the previous cache and fails when the media center is down', function () {
    Page::factory()->published()->create(['content_blocks' => gridBlocks('sosial')]);
    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::sequence()
        ->push([wpPost(1, 'Berita lama', '2026-10-12T00:00:00')])
        ->whenEmpty(Http::response([], 503)),
    ]);

    $service = app(MediaCenterApiService::class);
    expect($service->fetch('sosial', MediaCenterMode::Search, 6))->toHaveCount(1);

    $this->artisan('mediacenter:warm')->assertFailed();

    expect($service->fetch('sosial', MediaCenterMode::Search, 6)[0]->title)->toBe('Berita lama');
});
