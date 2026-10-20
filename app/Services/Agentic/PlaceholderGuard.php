<?php

namespace App\Services\Agentic;

/**
 * Penanda data yang belum pasti di draf asisten AI, berbentuk
 * `<mark>[Perlu dikonfirmasi: …]</mark>`. Artikel yang masih memuatnya tidak
 * boleh diajukan atau diterbitkan, supaya isian sementara tidak ikut tayang.
 */
class PlaceholderGuard
{
    public const MARKER = '[Perlu dikonfirmasi:';

    public const MESSAGE = "Masih ada bagian bertanda 'Perlu dikonfirmasi'. Lengkapi atau hapus dulu.";

    public static function has(?string $html): bool
    {
        return $html !== null && stripos($html, self::MARKER) !== false;
    }
}
