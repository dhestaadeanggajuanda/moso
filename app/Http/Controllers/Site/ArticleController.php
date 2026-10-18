<?php

namespace App\Http\Controllers\Site;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\SiteContext;
use App\Support\ThemeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Detail berita lokal di situs publik.
 */
class ArticleController extends Controller
{
    public function show(Request $request, Article $article, ThemeResolver $themes, SiteContext $site): Response
    {
        // Artikel yang belum terbit (atau dijadwalkan) diperlakukan seperti tidak ada.
        abort_unless(
            $article->status === ArticleStatus::Published && $article->published_at?->lte(now()),
            404,
        );

        $article->load(['author:id,name', 'categories:id,name,slug']);
        $theme = $themes->resolve();

        return Inertia::render('site/Article', [
            'article' => [
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                // Sudah disanitasi saat disimpan (HtmlSanitizer), aman untuk v-html.
                'body' => $article->body,
                'image_url' => $article->image_url,
                'image_alt' => $article->image_alt,
                'published_at' => $article->published_at?->toIso8601String(),
                'author' => $article->author->name,
                'categories' => $article->categories
                    ->map(fn (Category $category) => ['name' => $category->name, 'slug' => $category->slug])
                    ->values(),
            ],
            'theme' => $theme,
            'site' => $site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }
}
