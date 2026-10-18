<?php

namespace App\Http\Controllers\Site;

use App\Enums\PublicDocumentCategory;
use App\Http\Controllers\Controller;
use App\Models\PublicDocument;
use App\Support\SiteContext;
use App\Support\ThemeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar Informasi Publik (PPID) di situs publik, dikelompokkan per kategori
 * UU 14/2008. Hanya dokumen terbit yang tampil; berkasnya diunduh lewat route
 * `documents.ppid.download`, sehingga path storage tidak pernah sampai ke browser.
 */
class PublicDocumentController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct(
        private ThemeResolver $themes,
        private SiteContext $site,
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'kategori' => ['nullable', Rule::enum(PublicDocumentCategory::class)],
        ]);

        $category = PublicDocumentCategory::tryFrom((string) ($validated['kategori'] ?? ''));

        $documents = PublicDocument::query()
            ->published()
            ->when($category, fn (Builder $query, PublicDocumentCategory $category) => $query->where('category', $category->value))
            ->orderByDesc('year')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (PublicDocument $document) => $document->toPublicSummary());

        /** @var array<string, int> $counts */
        $counts = PublicDocument::query()
            ->published()
            ->toBase()
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $theme = $this->themes->resolve();

        return Inertia::render('site/Ppid', [
            'documents' => $documents,
            'categories' => array_map(fn (PublicDocumentCategory $item) => [
                'value' => $item->value,
                'label' => $item->label(),
                'description' => $item->description(),
                'count' => $counts[$item->value] ?? 0,
                'url' => route('site.ppid.index', ['kategori' => $item->value]),
            ], PublicDocumentCategory::cases()),
            'category' => $category?->value,
            'total' => array_sum($counts),
            'theme' => $theme,
            'site' => $this->site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }
}
