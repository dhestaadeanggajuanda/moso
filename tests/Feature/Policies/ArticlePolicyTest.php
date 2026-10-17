<?php

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;

test('every staff role may list and create articles', function (RoleName $role) {
    $user = userWithRole($role);

    expect($user->can('viewAny', Article::class))->toBeTrue()
        ->and($user->can('create', Article::class))->toBeTrue();
})->with([RoleName::SuperAdmin, RoleName::Approver, RoleName::Editor, RoleName::Kontributor]);

test('a kontributor may edit own articles only while draft or revision required', function (ArticleStatus $status, bool $allowed) {
    $kontributor = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->for($kontributor, 'author')->status($status)->create();

    expect($kontributor->can('update', $article))->toBe($allowed);
})->with([
    [ArticleStatus::Draft, true],
    [ArticleStatus::RevisionRequired, true],
    [ArticleStatus::Submitted, false],
    [ArticleStatus::ReadyToPublish, false],
    [ArticleStatus::Published, false],
    [ArticleStatus::Archived, false],
]);

test('a kontributor may not view, edit, or delete another author\'s article', function () {
    $kontributor = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->create();

    expect($kontributor->can('view', $article))->toBeFalse()
        ->and($kontributor->can('update', $article))->toBeFalse()
        ->and($kontributor->can('delete', $article))->toBeFalse();
});

test('a kontributor may delete own article only while draft', function (ArticleStatus $status, bool $allowed) {
    $kontributor = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->for($kontributor, 'author')->status($status)->create();

    expect($kontributor->can('delete', $article))->toBe($allowed);
})->with([
    [ArticleStatus::Draft, true],
    [ArticleStatus::RevisionRequired, false],
    [ArticleStatus::Submitted, false],
]);

test('editors, approvers, and super admins may edit and delete any article', function (RoleName $role) {
    $user = userWithRole($role);
    $article = Article::factory()->published()->create();

    expect($user->can('update', $article))->toBeTrue()
        ->and($user->can('delete', $article))->toBeTrue();
})->with([RoleName::SuperAdmin, RoleName::Approver, RoleName::Editor]);

test('nobody may force delete an article except through super admin bypass', function () {
    $article = Article::factory()->create();

    expect(userWithRole(RoleName::Approver)->can('forceDelete', $article))->toBeFalse()
        ->and(userWithRole(RoleName::SuperAdmin)->can('forceDelete', $article))->toBeTrue();
});
