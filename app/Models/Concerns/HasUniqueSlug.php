<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Mengisi kolom `slug` dari kolom sumber bila kosong, dan memastikan slug
 * unik (termasuk baris yang di-soft-delete, karena index unik di database
 * juga mencakupnya).
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::saving(function (self $model): void {
            $source = blank($model->slug)
                ? (string) $model->getAttribute($model->slugSource())
                : (string) $model->slug;

            $model->slug = $model->uniqueSlug($source);
        });
    }

    /**
     * Kolom yang dijadikan dasar slug.
     */
    protected function slugSource(): string
    {
        return 'title';
    }

    public function uniqueSlug(string $value): string
    {
        $base = Str::limit(Str::slug($value), 180, '') ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function slugTaken(string $slug): bool
    {
        // Tanpa global scope (termasuk SoftDeletingScope): index unik di database juga tidak mengenal scope.
        $query = static::query()->withoutGlobalScopes()->where('slug', $slug);

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->exists();
    }
}
