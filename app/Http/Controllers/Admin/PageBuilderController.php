<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCenterMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePageBlocksRequest;
use App\Models\Category;
use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Editor drag-and-drop untuk isi halaman (pages.content_blocks).
 */
class PageBuilderController extends Controller
{
    public function edit(Page $page, BlockRegistry $registry): Response
    {
        Gate::authorize('update', $page);

        return Inertia::render('admin/pages/Builder', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => ['value' => $page->status->value, 'label' => $page->status->label()],
                'updated_at' => $page->updated_at?->toIso8601String(),
            ],
            'blocks' => $registry->normalizeBlocks($page->content_blocks),
            'catalog' => $registry->catalog(),
            'categories' => Category::query()->orderBy('name')->get(['name', 'slug'])
                ->map(fn (Category $category) => ['value' => $category->slug, 'label' => $category->name]),
            'mediaCenterModes' => MediaCenterMode::options(),
        ]);
    }

    public function update(SavePageBlocksRequest $request, Page $page, BlockRegistry $registry): RedirectResponse
    {
        $page->content_blocks = $registry->normalizeBlocks($request->validated('blocks'));
        $page->updated_by = $request->user()?->id;
        $page->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Susunan halaman disimpan.']);

        return to_route('admin.pages.builder', $page);
    }
}
