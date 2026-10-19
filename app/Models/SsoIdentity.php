<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SsoIdentityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identitas SSO (OIDC) yang tertaut ke satu akun.
 *
 * Tidak ada kolom fillable: tautan hanya dibuat oleh App\Services\Sso\SsoAccountLinker
 * dari klaim ID token yang sudah divalidasi, tidak pernah dari input pengguna.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $subject
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
class SsoIdentity extends Model
{
    /** @use HasFactory<SsoIdentityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
