<?php

namespace App\Models;

use App\Enums\SakipDocumentType;
use Carbon\CarbonImmutable;
use Database\Factories\ESakipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen akuntabilitas kinerja (E-SAKIP) per tahun. Berkas di disk
 * `s3_private`, diunduh lewat route aplikasi selama berstatus terbit.
 *
 * `file_path_s3` dan `file_size` sengaja tidak fillable: diisi dari hasil unggahan.
 *
 * @property int $id
 * @property int $year
 * @property SakipDocumentType $document_type
 * @property string $document_title
 * @property string $file_path_s3
 * @property int $file_size
 * @property bool $is_published
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $creator
 * @property-read string $download_url
 */
#[Table('e_sakip')]
#[Fillable(['year', 'document_type', 'document_title', 'is_published'])]
class ESakip extends Model
{
    /** @use HasFactory<ESakipFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'document_type' => SakipDocumentType::class,
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
     * @return Attribute<string, never>
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::get(fn (): string => route('documents.sakip.download', $this->id));
    }
}
