<?php

namespace App\Http\Controllers\Site;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Services\News\NewsItem;
use App\Support\SiteContext;
use App\Support\ThemeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Indeks dan detail berita lokal di situs publik.
 */
class ArticleController extends Controller
{
    public const PER_PAGE = 12;

    /**
     * Semua berita dinas yang sudah terbit, terbaru dulu, dengan filter kategori opsional.
     * Kategori yang tidak dikenal dibalas 404, bukan daftar kosong.
     */
    public function index(Request $request, ThemeResolver $themes, SiteContext $site): Response
    {
        $validated = $request->validate([
            'kategori' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $category = filled($validated['kategori'] ?? null)
            ? Category::query()->where('slug', $validated['kategori'])->firstOrFail()
            : null;

        $articles = Article::query()
            ->published()
            ->when($category, fn (Builder $query, Category $category) => $query
                ->whereHas('categories', fn (Builder $categories) => $categories->whereKey($category->id)))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Article $article) => NewsItem::fromArticle($article)->toArray());

        // Hanya kategori yang punya berita terbit yang layak dijadikan filter.
        $categories = Category::query()
            ->whereHas('articles', fn (Builder $articles) => $articles->published())
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $item) => [
                'name' => $item->name,
                'slug' => $item->slug,
                'url' => route('site.articles.index', ['kategori' => $item->slug]),
            ])
            ->values();

        $theme = $themes->resolve();

        return Inertia::render('site/Articles', [
            'articles' => $articles,
            'categories' => $categories,
            'category' => $category ? ['name' => $category->name, 'slug' => $category->slug] : null,
            'theme' => $theme,
            'site' => $site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }

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
