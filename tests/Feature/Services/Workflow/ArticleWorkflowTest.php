<?php

use App\Enums\ArticleAction;
use App\Enums\ArticleCommentType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleWorkflowMode;
use App\Enums\RoleName;
use App\Exceptions\InvalidArticleTransition;
use App\Models\Article;
use App\Services\Workflow\ArticleWorkflow;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

// Tes ini menjaga alur Bertahap (empat mata); alur Sederhana diuji di ArticleSimpleWorkflowTest.
beforeEach(fn () => useArticleWorkflow(ArticleWorkflowMode::Review));

it('moves an article through review to publication and records each step', function () {
    $this->freezeSecond();
    $author = userWithRole(RoleName::Kontributor);
    $editor = userWithRole(RoleName::Editor);
    $approver = userWithRole(RoleName::Approver);
    $article = Article::factory()->for($author, 'author')->create();
    $workflow = app(ArticleWorkflow::class);

    $workflow->apply($article, ArticleAction::Submit, $author);
    $workflow->apply($article, ArticleAction::RequestRevision, $editor, 'Lengkapi sumber data.');
    $workflow->apply($article, ArticleAction::Submit, $author);
    $workflow->apply($article, ArticleAction::Approve, $editor);
    $workflow->apply($article, ArticleAction::Publish, $approver);

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at?->equalTo(now()))->toBeTrue();

    $history = $article->comments;
    expect($history)->toHaveCount(5)
        ->and($history->pluck('type')->unique()->all())->toBe([ArticleCommentType::StatusChange])
        ->and($history->map(fn ($entry) => [$entry->from_status, $entry->to_status])->all())->toBe([
            [ArticleStatus::Draft, ArticleStatus::Submitted],
            [ArticleStatus::Submitted, ArticleStatus::RevisionRequired],
            [ArticleStatus::RevisionRequired, ArticleStatus::Submitted],
            [ArticleStatus::Submitted, ArticleStatus::ReadyToPublish],
            [ArticleStatus::ReadyToPublish, ArticleStatus::Published],
        ])
        ->and($history[1]->body)->toBe('Lengkapi sumber data.')
        ->and($history[1]->user_id)->toBe($editor->id);
});

it('keeps a scheduled publication date', function () {
    $article = Article::factory()->status(ArticleStatus::ReadyToPublish)->create();
    $publishAt = now()->addDays(2)->startOfMinute()->toImmutable();

    app(ArticleWorkflow::class)->apply($article, ArticleAction::Publish, userWithRole(RoleName::Approver), publishAt: $publishAt);

    expect($article->fresh()->published_at?->equalTo($publishAt))->toBeTrue();
});

it('refuses an action the current status does not allow and changes nothing', function () {
    $article = Article::factory()->create();

    expect(fn () => app(ArticleWorkflow::class)->apply($article, ArticleAction::Publish, userWithRole(RoleName::Approver)))
        ->toThrow(InvalidArticleTransition::class);

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft)
        ->and($article->comments()->count())->toBe(0);
});

it('requires a note when requesting a revision', function () {
    $article = Article::factory()->status(ArticleStatus::Submitted)->create();

    expect(fn () => app(ArticleWorkflow::class)->apply($article, ArticleAction::RequestRevision, userWithRole(RoleName::Editor), '   '))
        ->toThrow(InvalidArgumentException::class);

    expect($article->fresh()->status)->toBe(ArticleStatus::Submitted);
});

it('writes every transition to the audit log', function () {
    $author = userWithRole(RoleName::Kontributor);
    $article = Article::factory()->for($author, 'author')->create();
    $audit = Mockery::spy(LoggerInterface::class);
    Log::partialMock()->shouldReceive('channel')->with('audit')->andReturn($audit);

    app(ArticleWorkflow::class)->apply($article, ArticleAction::Submit, $author);

    $audit->shouldHaveReceived('info')->with('article.status_changed', Mockery::on(fn (array $context) => $context['article_id'] === $article->id
        && $context['user_id'] === $author->id
        && $context['from'] === 'draft'
        && $context['to'] === 'submitted'));
});

it('only offers actions that fit the status, even to a super admin', function () {
    $article = Article::factory()->create();

    $actions = app(ArticleWorkflow::class)->availableActions($article, userWithRole(RoleName::SuperAdmin));

    expect($actions)->toBe([ArticleAction::Submit]);
});
