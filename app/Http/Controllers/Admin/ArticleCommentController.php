<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleCommentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleCommentRequest;
use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Komentar diskusi antara penulis dan reviewer pada riwayat artikel.
 */
class ArticleCommentController extends Controller
{
    public function store(StoreArticleCommentRequest $request, Article $article): RedirectResponse
    {
        $comment = new ArticleComment([
            'type' => ArticleCommentType::Comment,
            'body' => trim((string) $request->validated('body')),
        ]);
        $comment->article()->associate($article);
        $comment->user()->associate($request->user());
        $comment->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komentar ditambahkan.']);

        return to_route('admin.articles.show', $article);
    }
}
