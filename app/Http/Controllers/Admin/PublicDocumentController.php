<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicDocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicDocumentRequest;
use App\Models\PublicDocument;
use App\Services\Storage\SecureDocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dokumen informasi publik (PPID).
 */
class PublicDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PublicDocument::class);

        $filters = $request->validate([
            'category' => ['nullable', Rule::enum(PublicDocumentCategory::class)],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        return Inertia::render('admin/ppid/Index', [
            'documents' => PublicDocument::query()
                ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
                ->when($filters['year'] ?? null, fn (Builder $query, int|string $year) => $query->where('year', $year))
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (PublicDocument $document) => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'category' => ['value' => $document->category->value, 'label' => $document->category->label()],
                    'year' => $document->year,
                    'file_size' => $document->file_size,
                    'is_published' => $document->is_published,
                ]),
            'filters' => (object) $filters,
            'categories' => PublicDocumentCategory::options(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', PublicDocument::class);

        return Inertia::render('admin/ppid/Form', [
            'document' => null,
            'categories' => PublicDocumentCategory::options(),
        ]);
    }

    public function store(PublicDocumentRequest $request, SecureDocumentService $documents): RedirectResponse
    {
        $document = new PublicDocument($request->safe()->except(['file']));
        $document->created_by = $request->user()?->id;

        $stored = $documents->storePdf($request->file('file'), 'ppid');
        $document->file_path = $stored['path'];
        $document->file_size = $stored['size'];
        $this->syncPublishedAt($document);
        $document->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen PPID ditambahkan.']);

        return to_route('admin.ppid.index');
    }

    public function edit(PublicDocument $publicDocument): Response
    {
        Gate::authorize('update', $publicDocument);

        return Inertia::render('admin/ppid/Form', [
            'document' => [
                ...$publicDocument->only(['id', 'title', 'slug', 'description', 'year', 'file_size', 'is_published']),
                'category' => $publicDocument->category->value,
                'updated_at' => $publicDocument->updated_at?->toIso8601String(),
            ],
            'categories' => PublicDocumentCategory::options(),
        ]);
    }

    public function update(PublicDocumentRequest $request, PublicDocument $publicDocument, SecureDocumentService $documents): RedirectResponse
    {
        $publicDocument->fill($request->safe()->except(['file']));

        $oldPath = null;

        if ($request->hasFile('file')) {
            $oldPath = $publicDocument->file_path;
            $stored = $documents->storePdf($request->file('file'), 'ppid');
            $publicDocument->file_path = $stored['path'];
            $publicDocument->file_size = $stored['size'];
        }

        $this->syncPublishedAt($publicDocument);
        $publicDocument->save();
        $documents->delete($oldPath);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen PPID diperbarui.']);

        return to_route('admin.ppid.index');
    }

    public function destroy(PublicDocument $publicDocument, SecureDocumentService $documents): RedirectResponse
    {
        Gate::authorize('delete', $publicDocument);

        $publicDocument->delete();
        $documents->delete($publicDocument->file_path);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen PPID dihapus.']);

        return to_route('admin.ppid.index');
    }

    /**
     * Pratinjau berkas untuk pengelola, termasuk dokumen yang belum terbit.
     */
    public function file(PublicDocument $publicDocument, SecureDocumentService $documents): RedirectResponse
    {
        Gate::authorize('view', $publicDocument);

        return redirect()->away($documents->temporaryUrl($publicDocument->file_path, $publicDocument->slug.'.pdf'));
    }

    private function syncPublishedAt(PublicDocument $document): void
    {
        if ($document->is_published && $document->published_at === null) {
            $document->published_at = now();
        }
    }
}
