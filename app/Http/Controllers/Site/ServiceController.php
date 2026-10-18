<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\SiteContext;
use App\Support\ThemeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Standar Pelayanan di situs publik: daftar (dengan pencarian) dan detail layanan.
 */
class ServiceController extends Controller
{
    public const PER_PAGE = 12;

    public function __construct(
        private ThemeResolver $themes,
        private SiteContext $site,
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $services = Service::query()
            ->published()
            ->when($search !== '', fn ($query) => $query->searchName($search))
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Service $service) => $service->toPublicSummary());

        $theme = $this->themes->resolve();

        return Inertia::render('site/Services', [
            'services' => $services,
            'search' => $search,
            'theme' => $theme,
            'site' => $this->site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }

    public function show(Request $request, Service $service): Response
    {
        // Layanan yang belum terbit diperlakukan seperti tidak ada.
        abort_unless($service->is_published, 404);

        $theme = $this->themes->resolve();

        return Inertia::render('site/Service', [
            'service' => [
                ...$service->toPublicSummary(),
                'requirements' => $service->requirements,
                'procedure' => $service->procedure,
                'product' => $service->product,
                'complaint_channel' => $service->complaint_channel,
                'updated_at' => $service->updated_at?->toIso8601String(),
            ],
            'index_url' => route('site.services.index'),
            'theme' => $theme,
            'site' => $this->site->toArray($request),
        ])->withViewData(['theme' => $theme]);
    }
}
