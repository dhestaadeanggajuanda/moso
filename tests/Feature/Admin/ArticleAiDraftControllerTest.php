<?php

use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleAiDraft;
use App\Models\Category;
use App\Services\Agentic\AgenticClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const REMOTE_DRAFT_ID = '0199f1c2-7a3b-7c4d-9e5f-0123456789ab';

beforeEach(function () {
    fakeAgenticHost();
    Storage::fake('s3');
});

/**
 * Agentic menerima permintaan draf dan mengantrekannya.
 */
function fakeQueuedDraft(): void
{
    Http::fake(['agentic.test/api/v1/drafts' => Http::response(['data' => [
        'id' => REMOTE_DRAFT_ID, 'status' => 'queued', 'stage' => null,
    ]], 202)]);
}

describe('store', function () {
    it('redirects guests to the login page', function () {
        $this->post(route('admin.articles.ai-drafts.store'))->assertRedirect(route('login'));
    });

    it('queues a draft for a kontributor and keeps the remote id private', function () {
        fakeQueuedDraft();
        Category::factory()->create(['name' => 'Kesehatan', 'slug' => 'kesehatan']);
        $kontributor = userWithRole(RoleName::Kontributor);

        $response = $this->actingAs($kontributor)
            ->post(route('admin.articles.ai-drafts.store'), [
                'kind' => 'kegiatan',
                'title' => 'Posyandu Balita di Kelurahan Pasiran',
                'description' => 'Puskesmas menggelar posyandu.',
                'extra_facts' => 'Selasa, 14 Oktober 2026',
                'image' => UploadedFile::fake()->image('posyandu.jpg', 800, 600),
            ], ['Accept' => 'application/json'])
            ->assertAccepted()
            ->assertJson(['status' => 'queued', 'stage' => null]);

        $draft = ArticleAiDraft::query()->sole();

        expect($response->json('id'))->toBe($draft->id)->not->toBe(REMOTE_DRAFT_ID)
            ->and($draft->user_id)->toBe($kontributor->id)
            ->and($draft->article_id)->toBeNull()
            ->and($draft->remote_id)->toBe(REMOTE_DRAFT_ID);

        Http::assertSent(fn (Request $request) => multipartValue($request, 'kind') === 'kegiatan'
            && multipartValue($request, 'extra_facts') === 'Selasa, 14 Oktober 2026'
            && multipartValue($request, 'user_ref') === app(AgenticClient::class)->userReference($kontributor->id)
            && multipartValue($request, 'categories[0][slug]') === 'kesehatan'
            && $request->hasFile('image'));
    });

    it('attaches the stored article image instead of a url when no photo is uploaded', function () {
        fakeQueuedDraft();
        $kontributor = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($kontributor, 'author')->create(['image_path' => 'articles/2026/10/foto.webp']);
        Storage::disk('s3')->put('articles/2026/10/foto.webp', 'byte-webp');

        $this->actingAs($kontributor)
            ->postJson(route('admin.articles.ai-drafts.store'), [
                'kind' => 'berita',
                'title' => $article->title,
                'article_id' => $article->id,
            ])
            ->assertAccepted();

        expect(ArticleAiDraft::query()->sole()->article_id)->toBe($article->id);

        Http::assertSent(fn (Request $request) => $request->hasFile('image', 'byte-webp', 'foto.webp')
            && ! str_contains($request->body(), 'http'));
    });

    it('forbids requesting a draft for an article the user cannot edit', function () {
        Http::fake();
        $article = Article::factory()->create();

        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->postJson(route('admin.articles.ai-drafts.store'), [
                'kind' => 'berita',
                'title' => 'Judul',
                'article_id' => $article->id,
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    });

    it('rejects a photo that is not a safe image', function () {
        Http::fake();
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->postJson(route('admin.articles.ai-drafts.store'), ['kind' => 'berita', 'title' => 'Judul', 'image' => $svg])
            ->assertJsonValidationErrors(['image' => 'Kolom foto harus berupa gambar.']);

        Http::assertNothingSent();
    });

    it('requires a kind and a title', function () {
        Http::fake();

        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->postJson(route('admin.articles.ai-drafts.store'), [])
            ->assertJsonValidationErrors(['kind', 'title']);

        Http::assertNothingSent();
    });

    it('rewrites only from a previous draft owned by the same user', function () {
        fakeQueuedDraft();
        $kontributor = userWithRole(RoleName::Kontributor);
        $own = ArticleAiDraft::factory()->for($kontributor)->create();
        $foreign = ArticleAiDraft::factory()->create();
        $payload = ['kind' => 'berita', 'title' => 'Judul', 'instruction' => 'Lebih singkat.'];

        $this->actingAs($kontributor)
            ->postJson(route('admin.articles.ai-drafts.store'), [...$payload, 'previous_draft_id' => $foreign->id])
            ->assertJsonValidationErrors(['previous_draft_id']);

        $this->actingAs($kontributor)
            ->postJson(route('admin.articles.ai-drafts.store'), [...$payload, 'previous_draft_id' => $own->id])
            ->assertAccepted();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => multipartValue($request, 'previous_draft_id') === $own->remote_id
            && multipartValue($request, 'instruction') === 'Lebih singkat.');
    });

    it('answers 503 and stores nothing when the assistant is unreachable', function () {
        Http::fake(['agentic.test/*' => Http::failedConnection()]);

        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->postJson(route('admin.articles.ai-drafts.store'), ['kind' => 'berita', 'title' => 'Judul'])
            ->assertServiceUnavailable()
            ->assertJson(['message' => 'Asisten AI sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.']);

        expect(ArticleAiDraft::query()->count())->toBe(0);
    });

    it('is not found while the assistant is disabled', function () {
        config(['agentic.enabled' => false]);
        Http::fake();

        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->postJson(route('admin.articles.ai-drafts.store'), ['kind' => 'berita', 'title' => 'Judul'])
            ->assertNotFound();

        Http::assertNothingSent();
    });
});

describe('show', function () {
    it('returns the finished draft to its owner with sanitized html', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $category = Category::factory()->create(['slug' => 'kesehatan']);
        $draft = ArticleAiDraft::factory()->for($kontributor)->create(['remote_id' => REMOTE_DRAFT_ID]);
        Http::fake(['agentic.test/api/v1/drafts/'.REMOTE_DRAFT_ID => Http::response(['data' => agenticDoneDraft(REMOTE_DRAFT_ID, [
            'body_html' => '<h2 onclick="steal()">Kegiatan</h2><p>Posyandu <mark>[Perlu dikonfirmasi: tanggal]</mark>.</p><script>alert(1)</script>',
            'excerpt' => '<b>Ringkasan</b> singkat.',
            'category_slugs' => ['kesehatan', 'tidak-ada'],
        ])])]);

        $this->actingAs($kontributor)
            ->getJson(route('admin.articles.ai-drafts.show', $draft))
            ->assertOk()
            ->assertExactJson([
                'id' => $draft->id,
                'status' => 'done',
                'stage' => null,
                'error' => null,
                'result' => [
                    'title_suggestion' => 'Posyandu Balita di Kelurahan Pasiran',
                    'excerpt' => 'Ringkasan singkat.',
                    'body_html' => '<h2>Kegiatan</h2><p>Posyandu <mark>[Perlu dikonfirmasi: tanggal]</mark>.</p>',
                    'image_alt' => 'Petugas menimbang balita',
                    'needs_confirmation' => [['item' => 'tanggal', 'question' => 'Kapan kegiatan berlangsung?']],
                    'category_ids' => [$category->id],
                    'word_count' => 280,
                ],
            ]);
    });

    it('reports the current stage while the draft is being written', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $draft = ArticleAiDraft::factory()->for($kontributor)->create(['remote_id' => REMOTE_DRAFT_ID]);
        Http::fake(['agentic.test/api/v1/drafts/'.REMOTE_DRAFT_ID => Http::response(['data' => [
            'id' => REMOTE_DRAFT_ID, 'status' => 'processing', 'stage' => 'recalling', 'error' => null, 'result' => null,
        ]])]);

        $this->actingAs($kontributor)
            ->getJson(route('admin.articles.ai-drafts.show', $draft))
            ->assertOk()
            ->assertJson(['status' => 'processing', 'stage' => 'recalling', 'result' => null]);
    });

    it('passes on the reason a draft failed', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $draft = ArticleAiDraft::factory()->for($kontributor)->create(['remote_id' => REMOTE_DRAFT_ID]);
        Http::fake(['agentic.test/api/v1/drafts/'.REMOTE_DRAFT_ID => Http::response(['data' => [
            'id' => REMOTE_DRAFT_ID, 'status' => 'failed', 'stage' => null, 'error' => 'Foto tidak dapat dibaca.', 'result' => null,
        ]])]);

        $this->actingAs($kontributor)
            ->getJson(route('admin.articles.ai-drafts.show', $draft))
            ->assertOk()
            ->assertJson(['status' => 'failed', 'error' => 'Foto tidak dapat dibaca.', 'result' => null]);
    });

    it('hides a draft from everyone except its owner', function (RoleName $role) {
        Http::fake();
        $draft = ArticleAiDraft::factory()->for(userWithRole(RoleName::Kontributor))->create();

        $this->actingAs(userWithRole($role))
            ->getJson(route('admin.articles.ai-drafts.show', $draft))
            ->assertNotFound();

        Http::assertNothingSent();
    })->with([RoleName::Kontributor, RoleName::SuperAdmin]);

    it('answers 503 when the assistant is unreachable', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $draft = ArticleAiDraft::factory()->for($kontributor)->create();
        Http::fake(['agentic.test/*' => Http::failedConnection()]);

        $this->actingAs($kontributor)
            ->getJson(route('admin.articles.ai-drafts.show', $draft))
            ->assertServiceUnavailable();
    });
});

describe('linking a draft to the saved article', function () {
    it('links the authors own draft when the article is saved', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $draft = ArticleAiDraft::factory()->for($kontributor)->create();

        $this->actingAs($kontributor)
            ->post(route('admin.articles.store'), ['title' => 'Judul', 'body' => '<p>Isi</p>', 'ai_draft_id' => $draft->id])
            ->assertRedirect();

        expect($draft->fresh()->article_id)->toBe(Article::query()->sole()->id);
    });

    it('ignores a draft that belongs to another user', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($kontributor, 'author')->create();
        $foreign = ArticleAiDraft::factory()->create();

        $this->actingAs($kontributor)
            ->put(route('admin.articles.update', $article), ['title' => 'Judul', 'body' => '<p>Isi</p>', 'ai_draft_id' => $foreign->id])
            ->assertRedirect();

        expect($foreign->fresh()->article_id)->toBeNull();
    });
});
