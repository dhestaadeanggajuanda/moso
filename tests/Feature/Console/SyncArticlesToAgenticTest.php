<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeAgenticHost();
});

it('sends every published article since the given date and nothing else', function () {
    Http::fake(['agentic.test/api/v1/articles/*' => Http::response(['data' => []], 202)]);
    $recent = Article::factory()->published()->create(['published_at' => '2026-09-01 08:00:00']);
    $old = Article::factory()->published()->create(['published_at' => '2024-05-01 08:00:00']);
    $draft = Article::factory()->create();
    $archived = Article::factory()->status(ArticleStatus::Archived)->create(['published_at' => '2026-09-02 08:00:00']);

    $this->artisan('agentic:sync-articles', ['--since' => '2026-01-01'])
        ->expectsOutputToContain('1 artikel terkirim ke memori asisten AI.')
        ->assertSuccessful();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && str_ends_with($request->url(), "/articles/{$recent->id}"));
    Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), "/articles/{$old->id}")
        || str_ends_with($request->url(), "/articles/{$draft->id}")
        || str_ends_with($request->url(), "/articles/{$archived->id}"));
});

it('fails and asks for a rerun when agentic cannot be reached', function () {
    Http::fake(['agentic.test/*' => Http::failedConnection()]);
    Article::factory()->published()->count(2)->create();

    $this->artisan('agentic:sync-articles')
        ->expectsOutputToContain('0 artikel terkirim, 2 gagal')
        ->assertFailed();
});

it('refuses to run while the assistant is disabled', function () {
    config(['agentic.enabled' => false]);
    Http::fake();
    Article::factory()->published()->create();

    $this->artisan('agentic:sync-articles')
        ->expectsOutputToContain('Asisten AI belum aktif')
        ->assertFailed();

    Http::assertNothingSent();
});

it('rejects an unreadable since date', function () {
    Http::fake();

    $this->artisan('agentic:sync-articles', ['--since' => 'kemarin lusa'])
        ->expectsOutputToContain('Format --since tidak dikenali')
        ->assertFailed();

    Http::assertNothingSent();
});
