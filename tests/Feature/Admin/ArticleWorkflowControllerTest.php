<?php

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;

it('lets a kontributor submit their own draft for review', function () {
    $kontributor = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->for($kontributor, 'author')->create();

    $this->actingAs($kontributor)
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'submit'])
        ->assertRedirect(route('admin.articles.show', $article));

    expect($article->fresh()->status)->toBe(ArticleStatus::Submitted);
});

it('refuses to let a kontributor submit another author\'s article', function () {
    $article = Article::factory()->create();

    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'submit'])
        ->assertForbidden();

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);
});

it('refuses publishing by an editor', function () {
    $article = Article::factory()->status(ArticleStatus::ReadyToPublish)->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'publish'])
        ->assertForbidden();

    expect($article->fresh()->status)->toBe(ArticleStatus::ReadyToPublish);
});

it('refuses an author approving their own article', function () {
    $editor = userWithRole(RoleName::Editor);
    $article = Article::factory()->for($editor, 'author')->status(ArticleStatus::Submitted)->create();

    $this->actingAs($editor)
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'approve'])
        ->assertForbidden();
});

it('publishes an approved article so it appears on the public site', function () {
    $article = Article::factory()->status(ArticleStatus::ReadyToPublish)->create(['title' => 'Bantuan Pangan Tahap III']);

    $this->actingAs(userWithRole(RoleName::Approver))
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'publish'])
        ->assertRedirect(route('admin.articles.show', $article))
        ->assertSessionHasNoErrors();

    expect($article->fresh()->status)->toBe(ArticleStatus::Published);
    $this->withoutVite()->get(route('site.articles.show', $article))->assertOk();
});

it('requires a note when sending an article back for revision', function () {
    $article = Article::factory()->status(ArticleStatus::Submitted)->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'request_revision', 'note' => ''])
        ->assertInvalid(['note' => 'Tulis catatan revisi agar penulis tahu apa yang perlu diperbaiki.']);

    expect($article->fresh()->status)->toBe(ArticleStatus::Submitted);
});

it('rejects a past publication schedule and schedules on other actions', function () {
    $ready = Article::factory()->status(ArticleStatus::ReadyToPublish)->create();
    $submitted = Article::factory()->status(ArticleStatus::Submitted)->create();
    $approver = userWithRole(RoleName::Approver);

    $this->actingAs($approver)
        ->post(route('admin.articles.transitions.store', $ready), ['action' => 'publish', 'published_at' => now()->subDay()->toDateTimeString()])
        ->assertInvalid(['published_at' => 'Jadwal terbit tidak boleh di masa lalu.']);

    $this->actingAs($approver)
        ->post(route('admin.articles.transitions.store', $submitted), ['action' => 'approve', 'published_at' => now()->addDay()->toDateTimeString()])
        ->assertInvalid(['published_at']);
});

it('rejects unknown actions', function () {
    $article = Article::factory()->create();

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'delete_everything'])
        ->assertInvalid(['action']);
});

it('explains instead of overwriting when the status no longer allows the action', function () {
    // Super Admin melewati Policy, jadi aturan status di ArticleWorkflow yang menolak.
    $article = Article::factory()->create();

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.articles.transitions.store', $article), ['action' => 'publish'])
        ->assertInvalid(['action' => 'tidak bisa']);

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);
});
