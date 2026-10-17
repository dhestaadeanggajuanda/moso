<?php

use App\Enums\MediaCenterMode;
use App\Services\MediaCenter\MediaCenterApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    fakeMediaCenterHost();
});

it('resolves a tag slug and returns plain-text news items', function () {
    Http::fake([
        'mc.test/wp-json/wp/v2/tags*' => Http::response([['id' => 934]]),
        'mc.test/wp-json/wp/v2/posts*' => Http::response([
            wpPost(1, 'Bansos &#8211; Tahap <strong>II</strong><script>alert(1)</script>', '2026-10-15T21:08:13', [
                '_embedded' => ['wp:featuredmedia' => [['alt_text' => 'Penyerahan bantuan']]],
            ]),
        ]),
    ]);

    $items = app(MediaCenterApiService::class)->fetch('sosial', MediaCenterMode::Tag, 6);

    expect($items)->toHaveCount(1);
    expect($items[0]->title)->toBe('Bansos – Tahap IIalert(1)')->not->toContain('<')
        ->and($items[0]->excerpt)->toBe('Singkawang, MC – Ringkasan berita.')
        ->and($items[0]->source)->toBe('media_center')
        ->and($items[0]->imageUrl)->toBe('https://mc.test/wp-content/uploads/1-300.jpg')
        ->and($items[0]->imageAlt)->toBe('Penyerahan bantuan')
        // date_gmt 21:08 UTC = 04:08 WIB keesokan harinya.
        ->and($items[0]->publishedAt?->format('Y-m-d H:i'))->toBe('2026-10-16 04:08');

    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://mc.test/wp-json/wp/v2/tags')
        && $request['slug'] === 'sosial');
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://mc.test/wp-json/wp/v2/posts')
        && (int) $request['tags'] === 934
        && (int) $request['per_page'] === 6);
});

it('uses full text search without resolving a term in search mode', function () {
    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::response([wpPost(2, 'Pasar murah', '2026-10-12T12:00:00')])]);

    expect(app(MediaCenterApiService::class)->fetch('sosial', MediaCenterMode::Search))->toHaveCount(1);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request['search'] === 'sosial');
});

it('drops links and images that point outside the media center host', function () {
    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::response([
        wpPost(3, 'Tautan asing', '2026-10-12T12:00:00', ['link' => 'https://evil.test/phishing']),
        wpPost(4, 'Gambar asing', '2026-10-12T12:00:00', ['_embedded' => ['wp:featuredmedia' => [[
            'source_url' => 'https://evil.test/track.jpg',
            'media_details' => ['sizes' => ['medium' => ['source_url' => 'https://evil.test/track-300.jpg']]],
        ]]]]),
        wpPost(5, 'Skema berbahaya', '2026-10-12T12:00:00', ['link' => 'javascript:alert(1)']),
    ])]);

    $items = app(MediaCenterApiService::class)->fetch('sosial', MediaCenterMode::Search);

    expect($items)->toHaveCount(1)
        ->and($items[0]->title)->toBe('Gambar asing')
        ->and($items[0]->imageUrl)->toBeNull();
});

it('never sends a request for an invalid keyword', function (string $keyword) {
    Http::fake();

    expect(app(MediaCenterApiService::class)->fetch($keyword, MediaCenterMode::Search))->toBe([]);

    Http::assertNothingSent();
})->with(['../wp-admin', 'sosial?per_page=100', 'https://evil.test', 'SOSIAL', '']);

it('returns an empty list on failure without caching the failure', function () {
    Log::spy();

    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::sequence()
        ->push([], 500)->push([], 500)->push([], 500)
        ->push([wpPost(6, 'Sudah pulih', '2026-10-12T12:00:00')]),
    ]);

    $service = app(MediaCenterApiService::class);

    expect($service->fetch('sosial', MediaCenterMode::Search))->toBe([]);
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'tidak dapat diakses'));

    expect($service->fetch('sosial', MediaCenterMode::Search))->toHaveCount(1);
});

it('serves repeated requests from the cache', function () {
    Http::fake(['mc.test/wp-json/wp/v2/posts*' => Http::response([wpPost(7, 'Berita', '2026-10-12T12:00:00')])]);

    $service = app(MediaCenterApiService::class);
    $service->fetch('sosial', MediaCenterMode::Search, 4);
    $service->fetch('sosial', MediaCenterMode::Search, 4);

    Http::assertSentCount(1);
});
