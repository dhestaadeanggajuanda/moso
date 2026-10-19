<?php

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('s3');
});

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('admin.articles.index'))->assertRedirect(route('login'));
    });

    it('shows a kontributor only their own articles', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $own = Article::factory()->for($kontributor, 'author')->create();
        Article::factory()->create();

        $this->actingAs($kontributor)
            ->get(route('admin.articles.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/articles/Index')
                ->has('articles.data', 1)
                ->where('articles.data.0.id', $own->id));
    });

    it('shows an editor every article and filters by status', function () {
        Article::factory()->count(2)->create();
        $published = Article::factory()->published()->create();

        $this->actingAs(userWithRole(RoleName::Editor))
            ->get(route('admin.articles.index', ['status' => 'published']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles.data', 1)
                ->where('articles.data.0.id', $published->id));
    });

    it('treats LIKE wildcards in the search term literally', function () {
        Article::factory()->create(['title' => 'Diskon 100% untuk warga']);
        Article::factory()->create(['title' => 'Berita lain']);

        $this->actingAs(userWithRole(RoleName::Editor))
            ->get(route('admin.articles.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('articles.data', 1));
    });
});

describe('store', function () {
    it('creates a draft owned by the current user with a sanitized body and categories', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $category = Category::factory()->create();

        $this->actingAs($kontributor)
            ->post(route('admin.articles.store'), [
                'title' => 'Penyaluran Bantuan Sosial Tahap II',
                'body' => '<p onclick="steal()">Isi berita</p><script>alert(1)</script>',
                'category_ids' => [$category->id],
                // Kunci yang tidak boleh dikendalikan pengguna:
                'status' => 'published',
                'author_id' => 999,
            ])
            ->assertRedirect();

        $article = Article::query()->sole();

        expect($article->author_id)->toBe($kontributor->id)
            ->and($article->status)->toBe(ArticleStatus::Draft)
            ->and($article->slug)->toBe('penyaluran-bantuan-sosial-tahap-ii')
            ->and($article->body)->toBe('<p>Isi berita</p>')
            ->and($article->categories->pluck('id')->all())->toBe([$category->id]);
    });

    it('rejects an empty payload', function () {
        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->post(route('admin.articles.store'), [])
            ->assertInvalid(['title', 'body']);

        expect(Article::query()->count())->toBe(0);
    });

    it('re-encodes the uploaded image to webp on the public disk', function () {
        $this->actingAs(userWithRole(RoleName::Editor))
            ->post(route('admin.articles.store'), [
                'title' => 'Berita bergambar',
                'body' => '<p>Isi</p>',
                'image' => UploadedFile::fake()->image('foto.jpg', 2400, 1600),
                'image_alt' => 'Petugas menyerahkan bantuan',
            ])
            ->assertRedirect();

        $path = Article::query()->sole()->image_path;

        expect($path)->toStartWith('articles/')->toEndWith('.webp');
        Storage::disk('s3')->assertExists($path);

        [$width] = getimagesizefromstring(Storage::disk('s3')->get($path));
        expect($width)->toBe(1920);
    });

    it('requires alternative text when an image is uploaded', function () {
        $this->actingAs(userWithRole(RoleName::Editor))
            ->post(route('admin.articles.store'), [
                'title' => 'Berita bergambar',
                'body' => '<p>Isi</p>',
                'image' => UploadedFile::fake()->image('foto.jpg', 800, 600),
            ])
            ->assertInvalid(['image_alt']);
    });

    it('rejects svg uploads', function () {
        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
        );

        $this->actingAs(userWithRole(RoleName::Editor))
            ->post(route('admin.articles.store'), [
                'title' => 'Berita',
                'body' => '<p>Isi</p>',
                'image' => $svg,
                'image_alt' => 'Logo',
            ])
            ->assertInvalid(['image']);

        expect(Storage::disk('s3')->allFiles())->toBeEmpty();
    });
});

describe('update', function () {
    it('forbids a kontributor from editing another author\'s article', function () {
        $article = Article::factory()->create(['title' => 'Asli']);

        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->put(route('admin.articles.update', $article), ['title' => 'Diubah', 'body' => '<p>x</p>'])
            ->assertForbidden();

        expect($article->fresh()->title)->toBe('Asli');
    });

    it('forbids a kontributor from editing their submitted article', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($kontributor, 'author')->status(ArticleStatus::Submitted)->create();

        $this->actingAs($kontributor)
            ->get(route('admin.articles.edit', $article))
            ->assertForbidden();
    });

    it('lets an editor update any article and replaces the old image', function () {
        Storage::disk('s3')->put('articles/old.webp', 'old');
        $article = Article::factory()->create(['image_path' => 'articles/old.webp', 'image_alt' => 'Lama']);

        $this->actingAs(userWithRole(RoleName::Editor))
            ->put(route('admin.articles.update', $article), [
                'title' => 'Judul baru',
                'body' => '<p>Isi baru</p>',
                'image' => UploadedFile::fake()->image('baru.png', 800, 600),
                'image_alt' => 'Gambar baru',
            ])
            ->assertRedirect(route('admin.articles.edit', $article));

        $article->refresh();

        expect($article->title)->toBe('Judul baru')
            ->and($article->image_path)->not->toBe('articles/old.webp');
        Storage::disk('s3')->assertMissing('articles/old.webp');
        Storage::disk('s3')->assertExists($article->image_path);
    });

    it('stops an editor from changing an article that is already published', function () {
        $article = Article::factory()->published()->create(['title' => 'Judul terbit']);

        $this->actingAs(userWithRole(RoleName::Editor))
            ->put(route('admin.articles.update', $article), ['title' => 'Diubah diam-diam', 'body' => '<p>Isi</p>'])
            ->assertForbidden();

        expect($article->refresh()->title)->toBe('Judul terbit');
    });
});

describe('destroy', function () {
    it('soft deletes the article', function () {
        $article = Article::factory()->create();

        $this->actingAs(userWithRole(RoleName::Editor))
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertSoftDeleted($article);
    });

    it('stops an editor from taking down a published article', function () {
        $article = Article::factory()->published()->create();

        $this->actingAs(userWithRole(RoleName::Editor))
            ->delete(route('admin.articles.destroy', $article))
            ->assertForbidden();

        $this->assertNotSoftDeleted($article);
    });
});

describe('show', function () {
    it('shows an editor the review actions and history of a submitted article', function () {
        $article = Article::factory()->status(ArticleStatus::Submitted)->create();
        ArticleComment::factory()->for($article)->statusChange(ArticleStatus::Draft, ArticleStatus::Submitted)->create();

        $this->actingAs(userWithRole(RoleName::Editor))
            ->get(route('admin.articles.show', $article))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/articles/Show')
                ->where('actions.0.value', 'approve')
                ->where('actions.1.value', 'request_revision')
                ->where('actions.1.requires_note', true)
                ->has('actions', 2)
                ->has('history', 1)
                ->where('history.0.to.value', 'submitted'));
    });

    it('shows the author their submitted article without review actions', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($kontributor, 'author')->status(ArticleStatus::Submitted)->create();

        $this->actingAs($kontributor)
            ->get(route('admin.articles.show', $article))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('actions', [])
                ->where('can.update', false)
                ->where('can.comment', true));
    });

    it('hides another author\'s article from a kontributor', function () {
        $this->actingAs(userWithRole(RoleName::Kontributor))
            ->get(route('admin.articles.show', Article::factory()->create()))
            ->assertForbidden();
    });
});

describe('edit', function () {
    it('shows the author the latest revision note while revision is required', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($kontributor, 'author')->status(ArticleStatus::RevisionRequired)->create();
        ArticleComment::factory()->for($article)->statusChange(ArticleStatus::Submitted, ArticleStatus::RevisionRequired)->create(['body' => 'Catatan lama']);
        ArticleComment::factory()->for($article)->statusChange(ArticleStatus::Submitted, ArticleStatus::RevisionRequired)->create(['body' => 'Lengkapi lokasi penyaluran.']);

        $this->actingAs($kontributor)
            ->get(route('admin.articles.edit', $article))
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.revision_note.body', 'Lengkapi lokasi penyaluran.')
                ->where('article.actions.0.value', 'submit'));
    });

    it('shows no revision note once the article is back in draft', function () {
        $kontributor = userWithRole(RoleName::Kontributor);
        $article = Article::factory()->for($kontributor, 'author')->create();

        $this->actingAs($kontributor)
            ->get(route('admin.articles.edit', $article))
            ->assertInertia(fn (Assert $page) => $page->where('article.revision_note', null));
    });
});
