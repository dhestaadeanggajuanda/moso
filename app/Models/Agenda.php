<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Carbon\CarbonImmutable;
use Database\Factories\AgendaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $location
 * @property CarbonImmutable $start_at
 * @property CarbonImmutable|null $end_at
 * @property string|null $organizer
 * @property string|null $image_path
 * @property bool $is_published
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $creator
 * @property-read string|null $image_url
 */
#[Fillable(['title', 'slug', 'description', 'location', 'start_at', 'end_at', 'organizer', 'image_path', 'is_published'])]
class Agenda extends Model
{
    /** @use HasFactory<AgendaFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_published' => 'boolean',
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
     * Agenda terbit yang belum selesai, urut dari yang terdekat.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(fn (Builder $q) => $q
                ->where('end_at', '>=', now())
                ->orWhere(fn (Builder $q) => $q
                    ->whereNull('end_at')
                    ->where('start_at', '>=', now()->startOfDay())))
            ->orderBy('start_at')
            ->orderBy('id');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path
            ? Storage::disk('s3')->url($this->image_path)
            : null);
    }
}
