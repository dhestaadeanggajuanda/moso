<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private BlockRegistry $blocks) {}

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

        if ($request->validated('layout', 'text') === 'text') {
            $page->content_blocks = $this->textBlocks($page, $request);
        }

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
                'layout' => $page->isTextPage() ? 'text' : 'blocks',
                'body' => $page->textBody(),
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

        // Halaman blok disunting di Page Builder; isi di form hanya untuk halaman teks.
        if ($page->isTextPage() && $request->exists('body')) {
            $page->content_blocks = $this->textBlocks($page, $request);
        }

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

    /**
     * Isi halaman teks sebagai satu blok RichText. Normalisasi blok menyanitasi
     * HTML-nya (HtmlSanitizer) seperti blok Teks di Page Builder.
     *
     * @return list<array{id: string, type: string, props: array<string, mixed>}>
     */
    private function textBlocks(Page $page, PageRequest $request): array
    {
        $html = (string) $request->validated('body', '');

        if (trim(strip_tags($html)) === '' && ! str_contains($html, '<img')) {
            return [];
        }

        return $this->blocks->normalizeBlocks([[
            'id' => $page->content_blocks[0]['id'] ?? (string) Str::uuid(),
            'type' => 'RichText',
            'props' => ['html' => $html],
        ]]);
    }

    private function syncPublishedAt(Page $page): void
    {
        if ($page->status === PageStatus::Published && $page->published_at === null) {
            $page->published_at = now();
        }
    }
}
