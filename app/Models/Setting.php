<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan situs berbentuk key-value. Baca dan tulis lewat
 * App\Support\Settings, bukan langsung lewat model ini, supaya enkripsi
 * dan cache selalu konsisten.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value Nilai mentah (ciphertext bila is_encrypted)
 * @property bool $is_encrypted
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['key', 'value', 'is_encrypted'])]
#[Hidden(['value'])]
class Setting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }
}
