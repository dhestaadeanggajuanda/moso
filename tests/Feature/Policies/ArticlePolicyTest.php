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

test('editors, approvers, and super admins may edit and delete any article before it is published', function (RoleName $role, ArticleStatus $status) {
    $user = userWithRole($role);
    $article = Article::factory()->status($status)->create();

    expect($user->can('update', $article))->toBeTrue()
        ->and($user->can('delete', $article))->toBeTrue();
})->with([RoleName::SuperAdmin, RoleName::Approver, RoleName::Editor])
    ->with([ArticleStatus::Submitted, ArticleStatus::ReadyToPublish]);

test('only publishers may edit or delete a published or archived article', function (RoleName $role, ArticleStatus $status, bool $allowed) {
    $user = userWithRole($role);
    $article = Article::factory()->status($status)->create();

    expect($user->can('update', $article))->toBe($allowed)
        ->and($user->can('delete', $article))->toBe($allowed);
})->with([
    'editor, published' => [RoleName::Editor, ArticleStatus::Published, false],
    'editor, archived' => [RoleName::Editor, ArticleStatus::Archived, false],
    'approver, published' => [RoleName::Approver, ArticleStatus::Published, true],
    'approver, archived' => [RoleName::Approver, ArticleStatus::Archived, true],
    'super admin, published' => [RoleName::SuperAdmin, ArticleStatus::Published, true],
]);

test('a publisher may take down but not rewrite a published article they wrote', function () {
    $approver = userWithRole(RoleName::Approver);
    $article = Article::factory()->for($approver, 'author')->status(ArticleStatus::Published)->create();

    expect($approver->can('update', $article))->toBeFalse()
        ->and($approver->can('delete', $article))->toBeTrue();
});

test('nobody may force delete an article except through super admin bypass', function () {
    $article = Article::factory()->create();

    expect(userWithRole(RoleName::Approver)->can('forceDelete', $article))->toBeFalse()
        ->and(userWithRole(RoleName::SuperAdmin)->can('forceDelete', $article))->toBeTrue();
});

test('workflow abilities follow role, status, and authorship', function (RoleName $role, string $ability, ArticleStatus $status, bool $ownArticle, bool $allowed) {
    $user = userWithRole($role);
    $article = Article::factory()
        ->when($ownArticle, fn ($factory) => $factory->for($user, 'author'))
        ->status($status)
        ->create();

    expect($user->can($ability, $article))->toBe($allowed);
})->with([
    'kontributor submits own draft' => [RoleName::Kontributor, 'submit', ArticleStatus::Draft, true, true],
    'kontributor resubmits own revision' => [RoleName::Kontributor, 'submit', ArticleStatus::RevisionRequired, true, true],
    'kontributor cannot resubmit while in review' => [RoleName::Kontributor, 'submit', ArticleStatus::Submitted, true, false],
    'kontributor cannot submit another author\'s draft' => [RoleName::Kontributor, 'submit', ArticleStatus::Draft, false, false],
    'editor submits on behalf of an author' => [RoleName::Editor, 'submit', ArticleStatus::Draft, false, true],
    'editor approves another author\'s article' => [RoleName::Editor, 'approve', ArticleStatus::Submitted, false, true],
    'editor cannot approve own article' => [RoleName::Editor, 'approve', ArticleStatus::Submitted, true, false],
    'editor cannot approve a draft' => [RoleName::Editor, 'approve', ArticleStatus::Draft, false, false],
    'approver may also approve' => [RoleName::Approver, 'approve', ArticleStatus::Submitted, false, true],
    'kontributor cannot approve' => [RoleName::Kontributor, 'approve', ArticleStatus::Submitted, false, false],
    'editor requests revision in review' => [RoleName::Editor, 'requestRevision', ArticleStatus::Submitted, false, true],
    'editor requests revision when ready' => [RoleName::Editor, 'requestRevision', ArticleStatus::ReadyToPublish, false, true],
    'kontributor cannot request revision' => [RoleName::Kontributor, 'requestRevision', ArticleStatus::Submitted, true, false],
    'approver publishes another author\'s article' => [RoleName::Approver, 'publish', ArticleStatus::ReadyToPublish, false, true],
    'approver cannot publish own article' => [RoleName::Approver, 'publish', ArticleStatus::ReadyToPublish, true, false],
    'approver cannot publish before approval' => [RoleName::Approver, 'publish', ArticleStatus::Submitted, false, false],
    'editor cannot publish' => [RoleName::Editor, 'publish', ArticleStatus::ReadyToPublish, false, false],
    'approver archives a published article' => [RoleName::Approver, 'archive', ArticleStatus::Published, false, true],
    'editor cannot archive' => [RoleName::Editor, 'archive', ArticleStatus::Published, false, false],
    'approver republishes an archived article' => [RoleName::Approver, 'unarchive', ArticleStatus::Archived, false, true],
    'kontributor comments on own article' => [RoleName::Kontributor, 'comment', ArticleStatus::Submitted, true, true],
    'kontributor cannot comment on another author\'s article' => [RoleName::Kontributor, 'comment', ArticleStatus::Submitted, false, false],
    'editor comments on any article' => [RoleName::Editor, 'comment', ArticleStatus::Submitted, false, true],
]);
