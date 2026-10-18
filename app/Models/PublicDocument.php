<?php

namespace App\Models;

use App\Enums\PublicDocumentCategory;
use App\Models\Concerns\HasUniqueSlug;
use Carbon\CarbonImmutable;
use Database\Factories\PublicDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen informasi publik (PPID). Berkas disimpan di disk `s3_private` dan
 * hanya bisa diunduh lewat route aplikasi selama dokumen berstatus terbit.
 *
 * `file_path` dan `file_size` sengaja tidak fillable: diisi dari hasil unggahan.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property PublicDocumentCategory $category
 * @property int|null $year
 * @property string $file_path
 * @property int $file_size
 * @property bool $is_published
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $creator
 * @property-read string $download_url
 */
#[Fillable(['title', 'slug', 'description', 'category', 'year', 'is_published'])]
class PublicDocument extends Model
{
    /** @use HasFactory<PublicDocumentFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PublicDocumentCategory::class,
            'year' => 'integer',
            'file_size' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Data dokumen untuk situs publik. Path berkas di storage tidak pernah ikut dikirim.
     *
     * @return array{slug: string, title: string, description: string|null, category: array{value: string, label: string}, year: int|null, file_size: int, published_at: string|null, download_url: string}
     */
    public function toPublicSummary(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'year' => $this->year,
            'file_size' => $this->file_size,
            'published_at' => $this->published_at?->toIso8601String(),
            'download_url' => $this->download_url,
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::get(fn (): string => route('documents.ppid.download', $this->slug));
    }
}
