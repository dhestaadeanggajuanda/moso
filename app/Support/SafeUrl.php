<?php

namespace App\Support;

/**
 * Pola URL yang aman untuk tautan buatan pengelola (menu, tombol blok).
 *
 * Hanya path internal ("/profil") atau http(s) absolut. Skema lain
 * (javascript:, data:), URL protocol-relative ("//evil"), dan backslash
 * ("/\evil", yang dibaca browser sebagai "//evil") ditolak (OWASP A05).
 */
final class SafeUrl
{
    public const PATTERN = '/^(\/(?![\/\\\\])[^\s\\\\]*|https?:\/\/[^\s\\\\]+)$/i';

    public const MESSAGE = 'URL harus diawali "/" (halaman internal) atau "http(s)://".';
}
