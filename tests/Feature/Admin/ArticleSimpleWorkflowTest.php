<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleWorkflowMode;
use App\Enums\RoleName;
use App\Models\Article;
use App\Support\Settings;

/**
 * Alur Sederhana (bawaan): seperti WordPress, tanpa langkah setujui.
 */
function articlePayload(array $overrides = []): array
{
    return [
        'title' => 'Bakti sosial di Kelurahan Roban',
        'body' => '<p>Kegiatan bakti sosial berlangsung lancar.</p>',
        ...$overrides,
    ];
}

it('uses the simple workflow when nothing is configured', function () {
    expect(ArticleWorkflowMode::current())->toBe(ArticleWorkflowMode::Simple);
});

it('lets each role publish like WordPress', function (RoleName $role, bool $ownArticle, bool $canPublish) {
    $user = userWithRole($role);
    $article = Article::factory()
        ->when($ownArticle, fn ($factory) => $factory->for($user, 'author'))
        ->status(ArticleStatus::Draft)
        ->create();

    expect($user->can('publish', $article))->toBe($canPublish);
})->with([
    'editor, own draft' => [RoleName::Editor, true, true],
    'editor, someone else\'s draft' => [RoleName::Editor, false, true],
    'approver, own draft' => [RoleName::Approver, true, true],
    'penulis, own draft' => [RoleName::Penulis, true, true],
    'penulis, someone else\'s draft' => [RoleName::Penulis, false, false],
    'kontributor, own draft' => [RoleName::Kontributor, true, false],
]);

it('lets editors and authors keep their published articles up to date', function (RoleName $role, bool $ownArticle, bool $allowed) {
    $user = userWithRole($role);
    $article = Article::factory()
        ->when($ownArticle, fn ($factory) => $factory->for($user, 'author'))
        ->published()
        ->create();

    expect($user->can('update', $article))->toBe($allowed)
        ->and($user->can('delete', $article))->toBe($allowed);
})->with([
    'editor, someone else\'s article' => [RoleName::Editor, false, true],
    'penulis, own article' => [RoleName::Penulis, true, true],
    'penulis, someone else\'s article' => [RoleName::Penulis, false, false],
    'kontributor, own article' => [RoleName::Kontributor, true, false],
]);

it('publishes straight from the article form', function () {
    $editor = userWithRole(RoleName::Editor);

    $this->actingAs($editor)
        ->post(route('admin.articles.store'), articlePayload(['publish' => '1']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('inertia.flash_data.toast.message', 'Artikel diterbitkan.');

    $article = Article::query()->sole();

    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at)->not->toBeNull()
        ->and($article->comments()->count())->toBe(1);

    $this->get(route('site.articles.show', $article))->assertOk();
});

it('keeps the event date for an article about an older activity', function () {
    $eventDate = now()->subWeeks(2)->startOfMinute();

    $this->actingAs(userWithRole(RoleName::Penulis))
        ->post(route('admin.articles.store'), articlePayload([
            'publish' => '1',
            'published_at' => $eventDate->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHasNoErrors();

    $article = Article::query()->sole();

    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at->equalTo($eventDate))->toBeTrue();
});

it('schedules an article with a future date until that date', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.articles.store'), articlePayload([
            'publish' => '1',
            'published_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHas('inertia.flash_data.toast.message', 'Artikel disimpan dan dijadwalkan terbit.');

    $this->get(route('site.articles.show', Article::query()->sole()))->assertNotFound();
});

it('lets an editor correct the date of a published article', function () {
    $article = Article::factory()->published()->create();
    $correctDate = now()->subMonth()->startOfMinute();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.articles.update', $article), articlePayload([
            'title' => $article->title,
            'published_at' => $correctDate->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHasNoErrors();

    expect($article->refresh()->published_at->equalTo($correctDate))->toBeTrue();
});

it('saves a contributor\'s draft but never publishes it directly', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.articles.store'), articlePayload(['publish' => '1']))
        ->assertForbidden();

    expect(Article::query()->sole()->status)->toBe(ArticleStatus::Draft);
});

describe('workflow setting', function () {
    it('lets a super admin switch between the simple and review workflows', function () {
        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->put(route('admin.settings.workflow.update'), ['mode' => 'review'])
            ->assertRedirect(route('admin.settings.index'));

        expect(ArticleWorkflowMode::current())->toBe(ArticleWorkflowMode::Review);

        // Alur Bertahap: editor kembali tidak bisa menerbitkan langsung.
        $draft = Article::factory()->status(ArticleStatus::Draft)->create();
        expect(userWithRole(RoleName::Editor)->can('publish', $draft))->toBeFalse();
    });

    it('refuses everyone else and unknown modes', function () {
        $this->actingAs(userWithRole(RoleName::Editor))
            ->put(route('admin.settings.workflow.update'), ['mode' => 'review'])
            ->assertForbidden();

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->put(route('admin.settings.workflow.update'), ['mode' => 'anything'])
            ->assertInvalid(['mode']);

        expect(ArticleWorkflowMode::current())->toBe(ArticleWorkflowMode::Simple);
    });

    it('cannot be changed through the free text settings form', function () {
        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->put(route('admin.settings.update'), [
                'settings' => [['key' => ArticleWorkflowMode::SETTING_KEY, 'value' => 'review']],
            ])
            ->assertInvalid(['settings.0.key']);

        expect(app(Settings::class)->get(ArticleWorkflowMode::SETTING_KEY))->toBeNull();
    });
});
