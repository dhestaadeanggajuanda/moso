<?php

use App\Services\Agentic\AgenticClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    fakeAgenticHost();
});

it('queues a draft with the dinas token, the form fields, and the photo bytes', function () {
    Http::fake(['agentic.test/api/v1/drafts' => Http::response(['data' => [
        'id' => '0199f1c2-7a3b-7c4d-9e5f-0123456789ab', 'status' => 'queued', 'stage' => null,
    ]], 202)]);

    $draft = app(AgenticClient::class)->createDraft([
        'kind' => 'kegiatan',
        'title' => 'Posyandu Balita',
        'description' => null,
        'user_ref' => 'ref-penulis',
        'categories' => [['slug' => 'kesehatan', 'name' => 'Kesehatan']],
    ], 'byte-foto', 'foto.jpg');

    expect($draft)->toBe(['id' => '0199f1c2-7a3b-7c4d-9e5f-0123456789ab', 'status' => 'queued', 'stage' => null]);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://agentic.test/api/v1/drafts'
        && $request->hasHeader('Authorization', 'Bearer token-dinas-uji')
        && $request->isMultipart()
        && multipartValue($request, 'kind') === 'kegiatan'
        && multipartValue($request, 'title') === 'Posyandu Balita'
        && multipartValue($request, 'description') === null
        && multipartValue($request, 'categories[0][slug]') === 'kesehatan'
        && multipartValue($request, 'user_ref') === 'ref-penulis'
        && $request->hasFile('image', 'byte-foto', 'foto.jpg'));
});

it('does not resend a draft request the server already answered', function () {
    Http::fake(['agentic.test/api/v1/drafts' => Http::response(['message' => 'Server Error'], 500)]);

    expect(app(AgenticClient::class)->createDraft(['kind' => 'berita', 'title' => 'Judul', 'user_ref' => 'ref']))->toBeNull();

    Http::assertSentCount(1);
});

it('rejects a queued draft whose id is not a uuid', function () {
    Http::fake(['agentic.test/api/v1/drafts' => Http::response(['data' => ['id' => '../../admin', 'status' => 'queued']], 202)]);

    expect(app(AgenticClient::class)->createDraft(['kind' => 'berita', 'title' => 'Judul', 'user_ref' => 'ref']))->toBeNull();
});

it('retries a read after a server error', function () {
    Http::fake(['agentic.test/api/v1/stats' => Http::sequence()
        ->push(['message' => 'Bad Gateway'], 502)
        ->push(['data' => ['indexed_articles' => 12, 'drafts_30d' => 3, 'last_draft_at' => null]]),
    ]);

    expect(app(AgenticClient::class)->stats())->toBe(['indexed_articles' => 12, 'drafts_30d' => 3, 'last_draft_at' => null]);

    Http::assertSentCount(2);
});

it('does not retry a client error', function () {
    Http::fake(['agentic.test/api/v1/profile' => Http::response(['message' => 'Unauthenticated.'], 401)]);

    expect(app(AgenticClient::class)->profile())->toBeNull();

    Http::assertSentCount(1);
});

it('does not follow redirects away from the agentic service', function () {
    Http::fake([
        'agentic.test/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        '*' => Http::response(['data' => ['indexed_articles' => 1, 'drafts_30d' => 1, 'last_draft_at' => null]]),
    ]);

    expect(app(AgenticClient::class)->stats())->toBeNull();

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '169.254.169.254'));
});

it('returns null and logs only the operation when the service is unreachable', function () {
    Log::spy();
    Http::fake(['agentic.test/*' => Http::failedConnection()]);

    expect(app(AgenticClient::class)->createDraft(['kind' => 'berita', 'title' => 'Judul rahasia', 'user_ref' => 'ref']))->toBeNull();

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context === ['operation' => 'drafts.create', 'error' => 'ConnectionException']);
});

it('sends nothing while the assistant is disabled', function () {
    config(['agentic.enabled' => false]);
    Http::fake();

    $client = app(AgenticClient::class);

    expect($client->enabled())->toBeFalse()
        ->and($client->stats())->toBeNull()
        ->and($client->deleteArticle(1))->toBeFalse();

    Http::assertNothingSent();
});

it('upserts an article as json and treats deleting an unknown article as done', function () {
    Http::fake([
        'agentic.test/api/v1/articles/7' => fn (Request $request) => $request->method() === 'PUT'
            ? Http::response(['data' => ['queued' => true]], 202)
            : Http::response(['message' => 'Not Found'], 404),
    ]);

    $client = app(AgenticClient::class);
    $payload = [
        'title' => 'Judul',
        'excerpt' => null,
        'body_html' => '<p>Isi</p>',
        'published_at' => '2026-10-20T09:00:00+07:00',
        'url' => 'https://portal.test/berita/judul',
        'draft_id' => null,
    ];

    expect($client->upsertArticle(7, $payload))->toBeTrue()
        ->and($client->deleteArticle(7))->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && $request->isJson() && $request->data() === $payload);
});

it('wraps profile and style notes updates in the api contract', function () {
    Http::fake([
        'agentic.test/api/v1/profile' => Http::response(['data' => ['profile' => ['official_name' => 'Dinas Kesehatan'], 'updated_at' => '2026-10-20T09:00:00+07:00']]),
        'agentic.test/api/v1/style-notes' => Http::response(['data' => ['notes' => ['Pakai kalimat aktif.', 42], 'locked' => true, 'sample_count' => 5, 'updated_at' => null]]),
    ]);

    $client = app(AgenticClient::class);

    expect($client->putProfile(['official_name' => 'Dinas Kesehatan']))
        ->toBe(['profile' => ['official_name' => 'Dinas Kesehatan'], 'updated_at' => '2026-10-20T09:00:00+07:00'])
        ->and($client->putStyleNotes(['Pakai kalimat aktif.'], true))
        ->toBe(['notes' => ['Pakai kalimat aktif.'], 'locked' => true, 'sample_count' => 5, 'updated_at' => null]);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/profile') && $request->data() === ['profile' => ['official_name' => 'Dinas Kesehatan']]);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/style-notes') && $request->data() === ['notes' => ['Pakai kalimat aktif.'], 'locked' => true]);
});

it('identifies the author by a keyed hash instead of the user id', function () {
    $reference = app(AgenticClient::class)->userReference(42);

    expect($reference)->toHaveLength(40)
        ->toBe(substr(hash_hmac('sha256', '42', (string) config('app.key')), 0, 40));
});
