<?php

use App\Enums\ArticleCommentType;
use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;

it('stores a comment from the author on their own article as plain text', function () {
    $kontributor = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->for($kontributor, 'author')->status(ArticleStatus::Submitted)->create();

    $this->actingAs($kontributor)
        ->post(route('admin.articles.comments.store', $article), ['body' => '  Foto sudah saya ganti. <b>Mohon dicek</b>  '])
        ->assertRedirect(route('admin.articles.show', $article));

    $comment = $article->comments()->sole();
    expect($comment->type)->toBe(ArticleCommentType::Comment)
        ->and($comment->body)->toBe('Foto sudah saya ganti. <b>Mohon dicek</b>')
        ->and($comment->user_id)->toBe($kontributor->id);
});

it('refuses comments on another author\'s article from a kontributor', function () {
    $article = Article::factory()->create();

    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.articles.comments.store', $article), ['body' => 'Halo'])
        ->assertForbidden();

    expect($article->comments()->count())->toBe(0);
});

it('requires the comment text', function () {
    $article = Article::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.articles.comments.store', $article), ['body' => ''])
        ->assertInvalid(['body']);
});
