<?php

namespace App\Models;

use App\Enums\MenuLocation;
use App\Enums\PageStatus;
use Carbon\CarbonImmutable;
use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Item menu bertingkat (adjacency list). Tree dirakit oleh treeFor().
 *
 * @property int $id
 * @property MenuLocation $location
 * @property int|null $parent_id
 * @property string $label
 * @property int|null $page_id
 * @property string|null $url
 * @property bool $open_in_new_tab
 * @property int $sort_order
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Menu|null $parent
 * @property-read Collection<int, Menu> $children
 * @property-read Page|null $page
 * @property-read string|null $href
 */
#[Fillable(['location', 'parent_id', 'label', 'page_id', 'url', 'open_in_new_tab', 'sort_order', 'is_active'])]
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    public const MAX_DEPTH = 3;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Menu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Rakit tree menu untuk satu lokasi dengan satu query.
     *
     * Bila $publicOnly, item nonaktif (beserta seluruh anaknya) dan item yang
     * menautkan halaman belum terbit disembunyikan.
     *
     * @return Collection<int, Menu>
     */
    public static function treeFor(MenuLocation $location, bool $publicOnly = false): Collection
    {
        $items = static::query()
            ->with('page:id,slug,status')
            ->where('location', $location)
            ->when($publicOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->when($publicOnly, fn (Collection $menus) => $menus->filter(fn (Menu $menu) => $menu->isPubliclyLinkable()));

        $byParent = $items->groupBy(fn (Menu $menu) => $menu->parent_id ?? 0);

        $attachChildren = function (Menu $menu) use (&$attachChildren, $byParent): Menu {
            $children = $byParent->get($menu->id, new Collection)->map($attachChildren)->values();

            return $menu->setRelation('children', new Collection($children->all()));
        };

        return new Collection($byParent->get(0, new Collection)->map($attachChildren)->values()->all());
    }

    /**
     * Kedalaman item ini (item akar = 1).
     */
    public function depth(): int
    {
        $depth = 1;
        $parentId = $this->parent_id;

        while ($parentId !== null && $depth <= self::MAX_DEPTH + 1) {
            $depth++;
            $parentId = static::query()->whereKey($parentId)->value('parent_id');
        }

        return $depth;
    }

    /**
     * ID seluruh keturunan item ini (untuk mencegah siklus saat memindah induk).
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while ($frontier !== []) {
            // intval: sebagian driver mengembalikan ID sebagai string, sedangkan pemanggil membandingkan secara ketat.
            $frontier = array_values(array_map(intval(...), static::query()->whereIn('parent_id', $frontier)->pluck('id')->all()));
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    private function isPubliclyLinkable(): bool
    {
        if ($this->page_id === null) {
            return $this->url !== null;
        }

        return $this->page?->status === PageStatus::Published;
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function href(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->page ? '/'.$this->page->slug : $this->url);
    }
}
