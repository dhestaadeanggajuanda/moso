<?php

namespace App\Services\Sso;

/**
 * Pembacaan config/sso.php yang dipakai di beberapa tempat (route, middleware, halaman login).
 */
final class SsoConfig
{
    public static function enabled(): bool
    {
        return (bool) config('sso.enabled');
    }

    /**
     * Login lokal (password, passkey, reset password) hanya bisa dimatikan
     * bila SSO aktif, agar tidak ada yang terkunci saat SSO belum siap.
     */
    public static function localLoginEnabled(): bool
    {
        return ! self::enabled() || (bool) config('sso.local_login');
    }

    public static function provider(): string
    {
        return (string) config('sso.provider');
    }

    public static function label(): string
    {
        return (string) config('sso.label');
    }
}
