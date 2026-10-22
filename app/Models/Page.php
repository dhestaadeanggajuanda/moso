<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ReservedSlugs;
use Carbon\CarbonImmutable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Halaman hasil Page Builder. `content_blocks` berisi daftar blok
 * `{id, type, props}`; data dinamis disuntikkan saat render (Fase 3/4).
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property list<array{id: string, type: string, props: array<string, mixed>}> $content_blocks
 * @property string|null $theme
 * @property PageStatus $status
 * @property string|null $meta_description
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User|null $creator
 * @property-read User|null $editor
 */
#[Fillable(['title', 'slug', 'content_blocks', 'theme', 'status', 'meta_description'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasUniqueSlug, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'content_blocks' => '[]',
        'status' => 'draft',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'status' => PageStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * "Halaman teks": belum punya blok, atau hanya satu blok Teks. Isinya
     * disunting langsung di form halaman, tanpa Page Builder.
     */
    public function isTextPage(): bool
    {
        $blocks = $this->content_blocks;

        return $blocks === [] || (count($blocks) === 1 && $blocks[0]['type'] === 'RichText');
    }

    /**
     * Isi HTML halaman teks (sudah disanitasi saat disimpan), atau string kosong.
     */
    public function textBody(): string
    {
        return $this->isTextPage() ? (string) ($this->content_blocks[0]['props']['html'] ?? '') : '';
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PageStatus::Published);
    }

    /**
     * Slug otomatis tidak boleh bentrok dengan route sistem (mis. "berita" menjadi "berita-2").
     *
     * @return list<string>
     */
    protected function reservedSlugs(): array
    {
        return ReservedSlugs::LIST;
    }
}
