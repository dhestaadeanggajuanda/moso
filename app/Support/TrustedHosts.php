<?php

namespace App\Support;

/**
 * Pola host yang boleh dilayani aplikasi (middleware TrustHosts).
 *
 * Tanpa daftar ini, URL absolut (mis. tautan reset kata sandi di email)
 * dibangun dari header Host kiriman pengunjung, sehingga penyerang bisa
 * membuat korban menerima tautan ke domainnya sendiri (OWASP A07).
 */
class TrustedHosts
{
    /**
     * Host APP_URL ditambah `security.trusted_hosts`, masing-masing dicocokkan persis.
     *
     * @return list<string>
     */
    public static function patterns(): array
    {
        $hosts = [
            parse_url((string) config('app.url'), PHP_URL_HOST),
            ...config('security.trusted_hosts', []),
        ];

        return array_values(array_unique(array_map(
            fn (string $host): string => '^'.preg_quote(strtolower($host), '/').'$',
            array_filter($hosts, fn (mixed $host): bool => is_string($host) && $host !== ''),
        )));
    }
}
