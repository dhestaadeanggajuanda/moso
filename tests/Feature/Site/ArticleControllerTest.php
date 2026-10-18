<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
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
