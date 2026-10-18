<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Page::class);

        return Inertia::render('admin/pages/Index', [
            'pages' => Page::query()
                ->orderBy('title')
                ->get()
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'status' => ['value' => $page->status->value, 'label' => $page->status->label()],
                    'blocks_count' => count($page->content_blocks),
                    'updated_at' => $page->updated_at?->toIso8601String(),
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Page::class);

        return Inertia::render('admin/pages/Form', [
            'page' => null,
            'statuses' => PageStatus::options(),
            'themes' => config('themes.enabled'),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = new Page($request->validated());
        $page->created_by = $request->user()?->id;
        $page->updated_by = $request->user()?->id;
        $this->syncPublishedAt($page);
        $page->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman dibuat.']);

        return to_route('admin.pages.edit', $page);
    }

    public function edit(Page $page): Response
    {
        Gate::authorize('update', $page);

        return Inertia::render('admin/pages/Form', [
            'page' => [
                ...$page->only(['id', 'title', 'slug', 'meta_description', 'theme']),
                'status' => $page->status->value,
                'blocks_count' => count($page->content_blocks),
                'updated_at' => $page->updated_at?->toIso8601String(),
                'public_url' => route('site.pages.show', $page->slug),
            ],
            'statuses' => PageStatus::options(),
            'themes' => config('themes.enabled'),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->fill($request->validated());
        $page->updated_by = $request->user()?->id;
        $this->syncPublishedAt($page);
        $page->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman diperbarui.']);

        return to_route('admin.pages.edit', $page);
    }

    public function destroy(Page $page): RedirectResponse
    {
        Gate::authorize('delete', $page);

        $page->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman dihapus.']);

        return to_route('admin.pages.index');
    }

    private function syncPublishedAt(Page $page): void
    {
        if ($page->status === PageStatus::Published && $page->published_at === null) {
            $page->published_at = now();
        }
    }
}
