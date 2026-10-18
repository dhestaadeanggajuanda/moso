<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleAction;
use App\Exceptions\InvalidArticleTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransitionArticleRequest;
use App\Models\Article;
use App\Services\Workflow\ArticleWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Aksi alur persetujuan artikel (ajukan, setujui, minta revisi, terbitkan, arsipkan).
 */
class ArticleWorkflowController extends Controller
{
    public function store(TransitionArticleRequest $request, Article $article, ArticleWorkflow $workflow): RedirectResponse
    {
        /** @var ArticleAction $action */
        $action = $request->enum('action', ArticleAction::class);

        try {
            $workflow->apply(
                $article,
                $action,
                $request->user(),
                $request->validated('note'),
                $request->date('published_at')?->toImmutable(),
            );
        } catch (InvalidArticleTransition $exception) {
            // Status berubah sejak halaman dibuka (mis. reviewer lain lebih dulu): tolak, jangan timpa.
            throw ValidationException::withMessages(['action' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $action->doneMessage()]);

        return to_route('admin.articles.show', $article);
    }
}
