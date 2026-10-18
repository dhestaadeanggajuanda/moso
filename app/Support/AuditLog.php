<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Jejak audit keamanan (OWASP A09) ke channel log `audit`.
 *
 * Konteks hanya berisi ID, status, dan IP; jangan pernah memasukkan password,
 * token, atau isi konten ke sini.
 */
final class AuditLog
{
    public const CHANNEL = 'audit';

    /**
     * @param  array<string, scalar|null>  $context
     */
    public static function record(string $event, array $context = []): void
    {
        Log::channel(self::CHANNEL)->info($event, [
            ...$context,
            'ip' => request()->ip(),
        ]);
    }
}
