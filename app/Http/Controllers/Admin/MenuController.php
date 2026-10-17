<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MenuLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;
use App\Models\Menu;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Menu Builder bertingkat per lokasi (header/footer).
 */
class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Menu::class);

        $location = MenuLocation::from($request->validate([
            'location' => ['nullable', Rule::enum(MenuLocation::class)],
        ])['location'] ?? MenuLocation::Header->value);

        return Inertia::render('admin/menus/Index', [
            'location' => $location->value,
            'locations' => MenuLocation::options(),
            'tree' => $this->serializeTree(Menu::treeFor($location)),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Menu::class);

        $location = MenuLocation::tryFrom((string) $request->query('location')) ?? MenuLocation::Header;

        return Inertia::render('admin/menus/Form', [
            'menu' => null,
            'defaultLocation' => $location->value,
            ...$this->formOptions(),
        ]);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $menu = Menu::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item menu ditambahkan.']);

        return to_route('admin.menus.index', ['location' => $menu->location->value]);
    }

    public function edit(Menu $menu): Response
    {
        Gate::authorize('update', $menu);

        return Inertia::render('admin/menus/Form', [
            'menu' => [
                ...$menu->only(['id', 'parent_id', 'label', 'page_id', 'url', 'open_in_new_tab', 'sort_order', 'is_active']),
                'location' => $menu->location->value,
                'excluded_parent_ids' => [$menu->id, ...$menu->descendantIds()],
                'updated_at' => $menu->updated_at?->toIso8601String(),
            ],
            'defaultLocation' => $menu->location->value,
            ...$this->formOptions(),
        ]);
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item menu diperbarui.']);

        return to_route('admin.menus.index', ['location' => $menu->location->value]);
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        Gate::authorize('delete', $menu);

        // Sub-menu ikut terhapus (FK cascade).
        $menu->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item menu dihapus beserta sub-menunya.']);

        return to_route('admin.menus.index', ['location' => $menu->location->value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'locations' => MenuLocation::options(),
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'status'])
                ->map(fn (Page $page) => ['id' => $page->id, 'title' => $page->title, 'status' => $page->status->value]),
            // Calon induk: item yang masih menyisakan ruang kedalaman.
            'parents' => collect(MenuLocation::cases())->mapWithKeys(fn (MenuLocation $location) => [
                $location->value => $this->flattenForSelect(Menu::treeFor($location)),
            ]),
        ];
    }

    /**
     * @param  Collection<int, Menu>  $tree
     * @return list<array{id: int, label: string, depth: int}>
     */
    private function flattenForSelect(Collection $tree, int $depth = 1): array
    {
        $options = [];

        foreach ($tree as $menu) {
            if ($depth < Menu::MAX_DEPTH) {
                $options[] = ['id' => $menu->id, 'label' => $menu->label, 'depth' => $depth];
            }

            $options = [...$options, ...$this->flattenForSelect($menu->children, $depth + 1)];
        }

        return $options;
    }

    /**
     * @param  Collection<int, Menu>  $tree
     * @return list<array<string, mixed>>
     */
    private function serializeTree(Collection $tree): array
    {
        return array_values($tree->map(fn (Menu $menu) => [
            'id' => $menu->id,
            'label' => $menu->label,
            'href' => $menu->href,
            'is_active' => $menu->is_active,
            'open_in_new_tab' => $menu->open_in_new_tab,
            'sort_order' => $menu->sort_order,
            'children' => $this->serializeTree($menu->children),
        ])->all());
    }
}
