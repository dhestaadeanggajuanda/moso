<?php

use App\Enums\ArticleStatus;
use App\Http\Controllers\Site\ArticleController;
use App\Models\Article;
use App\Models\Category;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('shows a published article with its author', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Penyaluran Bantuan Sosial Tahap II',
        'body' => '<p>Isi berita.</p>',
    ]);

    $this->get(route('site.articles.show', $article))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Article')
            ->where('article.title', 'Penyaluran Bantuan Sosial Tahap II')
            ->where('article.body', '<p>Isi berita.</p>')
            ->where('article.author', $article->author->name)
            ->where('theme', 'TemaModern'));
});

it('hides articles that are not published yet', function (ArticleStatus $status) {
    $article = Article::factory()->status($status)->create();

    $this->get(route('site.articles.show', $article))->assertNotFound();
})->with([ArticleStatus::Draft, ArticleStatus::Submitted, ArticleStatus::ReadyToPublish]);

it('hides scheduled articles until their publish time', function () {
    $article = Article::factory()->published()->create(['published_at' => now()->addDay()]);

    $this->get(route('site.articles.show', $article))->assertNotFound();
});

it('lists published articles newest first on paginated pages', function () {
    Article::factory()->published()->count(ArticleController::PER_PAGE - 1)->create(['published_at' => now()->subDays(3)]);
    Article::factory()->published()->create(['title' => 'Berita Paling Baru', 'published_at' => now()->subHour()]);
    Article::factory()->published()->create(['title' => 'Berita Paling Lama', 'published_at' => now()->subMonth()]);
    Article::factory()->create(['title' => 'Masih Draf']);
    Article::factory()->published()->create(['title' => 'Terjadwal Besok', 'published_at' => now()->addDay()]);

    $this->get(route('site.articles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Articles')
            ->where('articles.total', ArticleController::PER_PAGE + 1)
            ->where('articles.last_page', 2)
            ->where('articles.data.0.title', 'Berita Paling Baru')
            ->where('articles.data.0.source', 'local')
            ->where('articles.data.0.url', route('site.articles.show', 'berita-paling-baru'))
            ->where('site.links.news', route('site.articles.index')))
        ->assertDontSee('Masih Draf')
        ->assertDontSee('Terjadwal Besok');

    $this->get(route('site.articles.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('articles.data', 1)
            ->where('articles.data.0.title', 'Berita Paling Lama'));
});

it('filters by category and only offers categories that have published news', function () {
    $sosial = Category::factory()->create(['name' => 'Sosial']);
    $draftOnly = Category::factory()->create(['name' => 'Kesehatan']);
    Category::factory()->create(['name' => 'Kosong']);

    Article::factory()->published()->hasAttached($sosial)->create(['title' => 'Penyaluran Bantuan Sosial']);
    Article::factory()->published()->create(['title' => 'Rapat Koordinasi Dinas']);
    Article::factory()->hasAttached($draftOnly)->create();

    $this->get(route('site.articles.index', ['kategori' => 'sosial']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('category', ['name' => 'Sosial', 'slug' => 'sosial'])
            ->where('articles.total', 1)
            ->where('articles.data.0.title', 'Penyaluran Bantuan Sosial')
            ->where('categories', [
                ['name' => 'Sosial', 'slug' => 'sosial', 'url' => route('site.articles.index', ['kategori' => 'sosial'])],
            ]));
});

it('answers an unknown category with 404 and rejects malformed ones', function () {
    $this->get(route('site.articles.index', ['kategori' => 'tidak-ada']))->assertNotFound();

    $this->get(route('site.articles.index', ['kategori' => "sosial' OR 1=1"]))->assertInvalid(['kategori']);
});
