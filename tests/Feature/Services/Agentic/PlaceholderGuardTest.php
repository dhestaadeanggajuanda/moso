<?php

use App\Enums\ArticleAction;
use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Exceptions\InvalidArticleTransition;
use App\Models\Article;
use App\Services\Agentic\PlaceholderGuard;
use App\Services\Workflow\ArticleWorkflow;

const UNCONFIRMED_BODY = '<p>Posyandu digelar pada <mark>[Perlu dikonfirmasi: tanggal]</mark>.</p>';

it('detects the unconfirmed-data marker', function (?string $html, bool $expected) {
    expect(PlaceholderGuard::has($html))->toBe($expected);
})->with([
    'marker from the assistant' => [UNCONFIRMED_BODY, true],
    'marker typed in lowercase' => ['<p>[perlu dikonfirmasi: lokasi]</p>', true],
    'plain article' => ['<p>Posyandu digelar Selasa.</p>', false],
    'empty body' => [null, false],
]);

it('refuses to submit or publish while the marker is in the body', function (ArticleStatus $status, ArticleAction $action, RoleName $role) {
    $article = Article::factory()->status($status)->create(['body' => UNCONFIRMED_BODY]);

    expect(fn () => app(ArticleWorkflow::class)->apply($article, $action, userWithRole($role)))
        ->toThrow(InvalidArticleTransition::class, "Masih ada bagian bertanda 'Perlu dikonfirmasi'. Lengkapi atau hapus dulu.");

    expect($article->fresh()->status)->toBe($status)
        ->and($article->comments()->count())->toBe(0);
})->with([
    'submit' => [ArticleStatus::Draft, ArticleAction::Submit, RoleName::Editor],
    'publish' => [ArticleStatus::ReadyToPublish, ArticleAction::Publish, RoleName::Approver],
]);

it('still lets a reviewer send the article back for revision', function () {
    $article = Article::factory()->status(ArticleStatus::Submitted)->create(['body' => UNCONFIRMED_BODY]);

    app(ArticleWorkflow::class)->apply($article, ArticleAction::RequestRevision, userWithRole(RoleName::Editor), 'Lengkapi tanggal kegiatan.');

    expect($article->fresh()->status)->toBe(ArticleStatus::RevisionRequired);
});

it('shows the author why the article cannot be submitted', function () {
    $author = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->for($author, 'author')->create(['body' => UNCONFIRMED_BODY]);

    $this->actingAs($author)
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'submit'])
        ->assertSessionHasErrors(['action' => "Masih ada bagian bertanda 'Perlu dikonfirmasi'. Lengkapi atau hapus dulu."]);

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);
});
