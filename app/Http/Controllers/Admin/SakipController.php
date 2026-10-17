<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SakipDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SakipRequest;
use App\Models\ESakip;
use App\Services\Storage\SecureDocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dokumen akuntabilitas kinerja (E-SAKIP).
 */
class SakipController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ESakip::class);

        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        return Inertia::render('admin/sakip/Index', [
            'documents' => ESakip::query()
                ->when($filters['year'] ?? null, fn (Builder $query, int|string $year) => $query->where('year', $year))
                ->orderByDesc('year')
                ->orderBy('document_type')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (ESakip $sakip) => [
                    'id' => $sakip->id,
                    'year' => $sakip->year,
                    'document_title' => $sakip->document_title,
                    'document_type' => ['value' => $sakip->document_type->value, 'label' => $sakip->document_type->label()],
                    'file_size' => $sakip->file_size,
                    'is_published' => $sakip->is_published,
                ]),
            'filters' => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ESakip::class);

        return Inertia::render('admin/sakip/Form', [
            'document' => null,
            'types' => SakipDocumentType::options(),
        ]);
    }

    public function store(SakipRequest $request, SecureDocumentService $documents): RedirectResponse
    {
        $sakip = new ESakip($request->safe()->except(['file']));
        $sakip->created_by = $request->user()?->id;

        $stored = $documents->storePdf($request->file('file'), 'sakip');
        $sakip->file_path_s3 = $stored['path'];
        $sakip->file_size = $stored['size'];
        $this->syncPublishedAt($sakip);
        $sakip->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen SAKIP ditambahkan.']);

        return to_route('admin.sakip.index');
    }

    public function edit(ESakip $sakip): Response
    {
        Gate::authorize('update', $sakip);

        return Inertia::render('admin/sakip/Form', [
            'document' => [
                ...$sakip->only(['id', 'year', 'document_title', 'file_size', 'is_published']),
                'document_type' => $sakip->document_type->value,
                'updated_at' => $sakip->updated_at?->toIso8601String(),
            ],
            'types' => SakipDocumentType::options(),
        ]);
    }

    public function update(SakipRequest $request, ESakip $sakip, SecureDocumentService $documents): RedirectResponse
    {
        $sakip->fill($request->safe()->except(['file']));

        $oldPath = null;

        if ($request->hasFile('file')) {
            $oldPath = $sakip->file_path_s3;
            $stored = $documents->storePdf($request->file('file'), 'sakip');
            $sakip->file_path_s3 = $stored['path'];
            $sakip->file_size = $stored['size'];
        }

        $this->syncPublishedAt($sakip);
        $sakip->save();
        $documents->delete($oldPath);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen SAKIP diperbarui.']);

        return to_route('admin.sakip.index');
    }

    public function destroy(ESakip $sakip, SecureDocumentService $documents): RedirectResponse
    {
        Gate::authorize('delete', $sakip);

        $sakip->delete();
        $documents->delete($sakip->file_path_s3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen SAKIP dihapus.']);

        return to_route('admin.sakip.index');
    }

    /**
     * Pratinjau berkas untuk pengelola, termasuk dokumen yang belum terbit.
     */
    public function file(ESakip $sakip, SecureDocumentService $documents): RedirectResponse
    {
        Gate::authorize('view', $sakip);

        return redirect()->away(
            $documents->temporaryUrl($sakip->file_path_s3, "sakip-{$sakip->year}-{$sakip->document_type->value}.pdf"),
        );
    }

    private function syncPublishedAt(ESakip $sakip): void
    {
        if ($sakip->is_published && $sakip->published_at === null) {
            $sakip->published_at = now();
        }
    }
}
