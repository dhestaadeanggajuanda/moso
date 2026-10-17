<?php

namespace App\Support;

/**
 * Pola URL yang aman untuk tautan buatan pengelola (menu, tombol blok).
 *
 * Hanya path internal ("/profil") atau http(s) absolut. Skema lain
 * (javascript:, data:) dan URL protocol-relative ("//evil") ditolak (OWASP A05).
 */
final class SafeUrl
{
    public const PATTERN = '/^(\/(?!\/)\S*|https?:\/\/\S+)$/i';

    public const MESSAGE = 'URL harus diawali "/" (halaman internal) atau "http(s)://".';
}
