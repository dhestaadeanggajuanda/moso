<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageBuilder\BlockHydrator;
use App\Support\Settings;
use App\Support\SiteContext;
use App\Support\ThemeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman publik hasil Page Builder, dirender oleh tema aktif.
 */
class PageController extends Controller
{
    public function __construct(
        private BlockHydrator $hydrator,
        private ThemeResolver $themes,
        private SiteContext $site,
    ) {}

    public function home(Request $request, Settings $settings): Response
    {
        $page = Page::query()
            ->published()
            ->where('slug', $settings->get('site.home_page', 'beranda'))
            ->first();

        return $this->render($request, $page);
    }

    public function show(Request $request, Page $page): Response
    {
        abort_unless($page->status === PageStatus::Published, 404);

        return $this->render($request, $page);
    }

    /**
     * Beranda tetap tampil (kosong) bila halaman beranda belum dibuat.
     */
    private function render(Request $request, ?Page $page): Response
    {
        $theme = $this->themes->resolve($page?->theme);

        return Inertia::render('site/Show', [
            'page' => $page ? [
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_description' => $page->meta_description,
            ] : null,
            'blocks' => $page ? $this->hydrator->hydrate($page->content_blocks) : [],
            'theme' => $theme,
            'site' => $this->site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }
}
