<?php

namespace App\Http\Controllers\Site;

use App\Enums\SakipDocumentType;
use App\Http\Controllers\Controller;
use App\Models\ESakip;
use App\Support\SiteContext;
use App\Support\ThemeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dokumen E-SAKIP di situs publik, dikelompokkan per tahun lalu diurutkan
 * menurut siklus SAKIP (Renstra sampai LKjIP). Hanya dokumen terbit yang
 * tampil; berkasnya diunduh lewat route `documents.sakip.download`.
 *
 * Tanpa paginasi: tiap tahun hanya memuat enam jenis dokumen (plus revisi).
 */
class SakipController extends Controller
{
    public function __construct(
        private ThemeResolver $themes,
        private SiteContext $site,
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'jenis' => ['nullable', Rule::enum(SakipDocumentType::class)],
        ]);

        $year = isset($validated['tahun']) ? (int) $validated['tahun'] : null;
        $type = SakipDocumentType::tryFrom((string) ($validated['jenis'] ?? ''));

        $documents = ESakip::query()
            ->published()
            ->when($year, fn (Builder $query, int $year) => $query->where('year', $year))
            ->when($type, fn (Builder $query, SakipDocumentType $type) => $query->where('document_type', $type->value))
            ->orderByDesc('year')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $typeOrder = array_flip(array_map(fn (SakipDocumentType $case) => $case->value, SakipDocumentType::cases()));

        $groups = $documents
            ->groupBy('year')
            ->map(fn (Collection $items, int $groupYear) => [
                'year' => $groupYear,
                'documents' => $items
                    ->sortBy(fn (ESakip $document) => $typeOrder[$document->document_type->value])
                    ->map(fn (ESakip $document) => $document->toPublicSummary())
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $theme = $this->themes->resolve();

        return Inertia::render('site/Sakip', [
            'groups' => $groups,
            'total' => $documents->count(),
            'filters' => [
                'year' => $year,
                'type' => $type?->value,
            ],
            'yearOptions' => ESakip::query()
                ->published()
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn (mixed $value): int => (int) $value)
                ->values()
                ->all(),
            'typeOptions' => SakipDocumentType::options(),
            'theme' => $theme,
            'site' => $this->site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }
}
