<?php

namespace App\Support;

/**
 * Slug yang dipakai route aplikasi dan tidak boleh menjadi slug halaman.
 * Dipakai bersama oleh validasi halaman dan route publik `/{slug}`.
 */
final class ReservedSlugs
{
    public const LIST = [
        'admin', 'api', 'agenda', 'berita', 'build', 'dashboard', 'email', 'forgot-password',
        'layanan', 'login', 'logout', 'ppid', 'register', 'reset-password', 'sakip', 'settings',
        'storage', 'two-factor-challenge', 'unduh', 'up', 'user',
    ];

    /**
     * Regex parameter route: slug huruf kecil/angka/tanda hubung, selain slug sistem.
     */
    public static function routePattern(): string
    {
        return '(?!(?:'.implode('|', array_map(preg_quote(...), self::LIST)).')$)[a-z0-9]+(?:-[a-z0-9]+)*';
    }
}
