<?php

namespace App\Services\Sso;

/**
 * Identitas dari ID token OIDC yang sudah divalidasi (tanda tangan, iss, aud,
 * exp, nonce). Hanya objek ini yang boleh dipakai untuk menautkan atau
 * meloginkan akun.
 */
final readonly class OidcIdentity
{
    public function __construct(
        public string $subject,
        public ?string $email,
        public bool $emailVerified,
        public ?string $name,
        public bool $mfa,
        public string $idToken,
    ) {}
}
