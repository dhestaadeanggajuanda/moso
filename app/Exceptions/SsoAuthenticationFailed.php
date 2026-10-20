<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Login SSO ditolak. `reason` adalah kode pendek untuk log audit (tanpa token
 * atau data sensitif); pengguna hanya melihat pesan umum dari userMessage().
 */
class SsoAuthenticationFailed extends RuntimeException
{
    public function __construct(public readonly string $reason, ?Throwable $previous = null)
    {
        parent::__construct("Login SSO ditolak: {$reason}", previous: $previous);
    }

    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, $previous);
    }

    /**
     * Pesan untuk halaman login. Akun yang belum terdaftar dibedakan agar
     * pegawai tahu harus menghubungi admin, tanpa membocorkan detail teknis.
     */
    public function userMessage(): string
    {
        return match ($this->reason) {
            'not_registered', 'email_unverified' => 'Akun SSO Anda belum terdaftar di portal ini. Hubungi admin dinas.',
            'account_deactivated' => 'Akun portal Anda dinonaktifkan. Hubungi admin dinas.',
            'already_linked' => 'Akun portal dengan email ini sudah tertaut ke akun SSO lain. Hubungi admin dinas.',
            'access_denied' => 'Login SSO dibatalkan.',
            default => 'Login SSO gagal. Coba lagi, atau hubungi admin bila masalah berlanjut.',
        };
    }
}
