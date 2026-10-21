<?php

use App\Enums\ArticleAction;
use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Listeners\SyncArticleToAgentic;
use App\Models\Article;
use App\Models\ArticleAiDraft;
use App\Services\Workflow\ArticleWorkflow;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    fakeAgenticHost();
});

describe('queueing', function () {
    it('queues a memory sync when an article is published', function () {
        Queue::fake([CallQueuedListener::class]);
        $article = Article::factory()->status(ArticleStatus::ReadyToPublish)->create();

        app(ArticleWorkflow::class)->apply($article, ArticleAction::Publish, userWithRole(RoleName::Approver));

        expect(Queue::listenersPushed(SyncArticleToAgentic::class, fn ($event) => $event instanceof ArticlePublished && $event->article->is($article)))
            ->toHaveCount(1);
    });

    it('queues a removal when a published article is archived', function () {
        Queue::fake([CallQueuedListener::class]);
        $article = Article::factory()->published()->create();

        app(ArticleWorkflow::class)->apply($article, ArticleAction::Archive, userWithRole(RoleName::Approver));

        expect(Queue::listenersPushed(SyncArticleToAgentic::class, fn ($event) => $event instanceof ArticleUnpublished && $event->article->is($article)))
            ->toHaveCount(1);
    });

    it('queues a resync when a published article is edited and a removal when it is deleted', function () {
        Queue::fake([CallQueuedListener::class]);
        $article = Article::factory()->published()->create();
        $approver = userWithRole(RoleName::Approver);

        $this->actingAs($approver)
            ->put(route('admin.articles.update', $article), ['title' => 'Judul diperbaiki', 'body' => '<p>Isi diperbaiki</p>'])
            ->assertRedirect();
        $this->actingAs($approver)->delete(route('admin.articles.destroy', $article))->assertRedirect();

        expect(Queue::listenersPushed(SyncArticleToAgentic::class, fn ($event) => $event instanceof ArticlePublished))->toHaveCount(1)
            ->and(Queue::listenersPushed(SyncArticleToAgentic::class, fn ($event) => $event instanceof ArticleUnpublished))->toHaveCount(1);
    });

    it('does not sync drafts that are still being written', function () {
        Queue::fake([CallQueuedListener::class]);
        $author = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($author, 'author')->create();

        $this->actingAs($author)
            ->put(route('admin.articles.update', $article), ['title' => 'Judul', 'body' => '<p>Isi</p>'])
            ->assertRedirect();
        app(ArticleWorkflow::class)->apply($article, ArticleAction::Submit, $author);

        Queue::assertNothingPushed();
    });

    it('queues nothing while the assistant is disabled', function () {
        config(['agentic.enabled' => false]);
        Queue::fake([CallQueuedListener::class]);
        $article = Article::factory()->status(ArticleStatus::ReadyToPublish)->create();

        app(ArticleWorkflow::class)->apply($article, ArticleAction::Publish, userWithRole(RoleName::Approver));

        Queue::assertNothingPushed();
    });
});

describe('handling', function () {
    it('upserts the published article with the latest linked ai draft', function () {
        Http::fake(['agentic.test/api/v1/articles/*' => Http::response(['data' => []], 202)]);
        $article = Article::factory()->published()->create(['title' => 'Posyandu Balita', 'slug' => 'posyandu-balita', 'excerpt' => null]);
        ArticleAiDraft::factory()->for($article)->create(['remote_id' => '0199f1c2-0000-7000-8000-000000000001', 'updated_at' => now()->subDay()]);
        ArticleAiDraft::factory()->for($article)->create(['remote_id' => '0199f1c2-0000-7000-8000-000000000002', 'updated_at' => now()]);

        app(SyncArticleToAgentic::class)->handle(new ArticlePublished($article));

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && $request->url() === "https://agentic.test/api/v1/articles/{$article->id}"
            && $request->data() === [
                'title' => 'Posyandu Balita',
                'excerpt' => null,
                'body_html' => $article->body,
                'published_at' => $article->published_at?->toIso8601String(),
                'url' => route('site.articles.show', 'posyandu-balita'),
                'draft_id' => '0199f1c2-0000-7000-8000-000000000002',
            ]);
    });

    it('removes the article instead when it was archived before the job ran', function () {
        Http::fake(['agentic.test/api/v1/articles/*' => Http::response(status: 204)]);
        $article = Article::factory()->status(ArticleStatus::Archived)->create(['published_at' => now()->subDay()]);

        app(SyncArticleToAgentic::class)->handle(new ArticlePublished($article));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && $request->url() === "https://agentic.test/api/v1/articles/{$article->id}");
    });

    it('removes a deleted article from memory', function () {
        Http::fake(['agentic.test/api/v1/articles/*' => Http::response(status: 204)]);
        $article = Article::factory()->published()->create();
        $article->delete();

        app(SyncArticleToAgentic::class)->handle(new ArticleUnpublished($article));

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
    });

    it('fails the job so the queue retries when agentic is unreachable', function () {
        Http::fake(['agentic.test/*' => Http::failedConnection()]);
        $article = Article::factory()->published()->create();

        expect(fn () => app(SyncArticleToAgentic::class)->handle(new ArticlePublished($article)))
            ->toThrow(RuntimeException::class);
    });
});
