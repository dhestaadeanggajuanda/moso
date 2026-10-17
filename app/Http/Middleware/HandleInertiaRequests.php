<?php

namespace App\Http\Middleware;

use App\Models\Agenda;
use App\Models\Article;
use App\Models\Category;
use App\Models\ESakip;
use App\Models\Menu;
use App\Models\Page;
use App\Models\PublicDocument;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->getRoleNames() : [],
                // Hanya untuk menampilkan/menyembunyikan menu. Otorisasi sebenarnya tetap di Policy.
                'can' => $user ? [
                    'articles' => $user->can('viewAny', Article::class),
                    'categories' => $user->can('viewAny', Category::class),
                    'pages' => $user->can('viewAny', Page::class),
                    'agendas' => $user->can('viewAny', Agenda::class),
                    'ppid' => $user->can('viewAny', PublicDocument::class),
                    'services' => $user->can('viewAny', Service::class),
                    'sakip' => $user->can('viewAny', ESakip::class),
                    'menus' => $user->can('viewAny', Menu::class),
                    'settings' => $user->can('viewAny', Setting::class),
                ] : (object) [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
