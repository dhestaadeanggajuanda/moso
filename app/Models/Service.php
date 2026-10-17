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

    protected function slugSource(): string
    {
        return 'name';
    }
}
