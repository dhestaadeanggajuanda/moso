<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Carbon\CarbonImmutable;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Layanan publik dinas beserta komponen Standar Pelayanan.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property list<string> $requirements
 * @property list<string> $procedure
 * @property string $processing_time
 * @property string $cost
 * @property string|null $product
 * @property string|null $complaint_channel
 * @property int $sort_order
 * @property bool $is_published
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'description', 'requirements', 'procedure', 'processing_time',
    'cost', 'product', 'complaint_channel', 'sort_order', 'is_published',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'requirements' => '[]',
        'procedure' => '[]',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'procedure' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Layanan terbit, urut sesuai urutan tampil lalu nama.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Cari nama layanan secara harfiah: % dan _ dari pengguna bukan wildcard.
     * Karakter escape "!" eksplisit supaya perilakunya sama di MySQL dan SQLite.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function searchName(Builder $query, string $term): void
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        $query->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
    }

    /**
     * Layanan tanpa biaya ("Gratis", "Rp 0", "Tidak dipungut biaya").
     */
    public function isFree(): bool
    {
        return preg_match('/^(gratis|tidak dipungut biaya|rp\.?\s*0(,-|,00)?)$/i', trim($this->cost)) === 1;
    }

    /**
     * Ringkasan layanan untuk daftar di situs publik.
     *
     * @return array{id: int, name: string, slug: string, description: string|null, processing_time: string, cost: string, is_free: bool, url: string}
     */
    public function toPublicSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'processing_time' => $this->processing_time,
            'cost' => $this->cost,
            'is_free' => $this->isFree(),
            'url' => route('site.services.show', $this->slug),
        ];
    }

    protected function slugSource(): string
    {
        return 'name';
    }
}
