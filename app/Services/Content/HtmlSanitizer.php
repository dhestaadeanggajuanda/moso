<?php

namespace App\Services\Content;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Membersihkan HTML dari editor (body artikel) sebelum disimpan (OWASP A05).
 *
 * Hanya elemen dan atribut "aman" menurut W3C Sanitizer API yang dipertahankan:
 * <script>, event handler (onerror, onclick, ...), style inline, iframe, dan
 * URL javascript: dibuang. Gambar hanya boleh berasal dari bucket S3 sendiri.
 */
class HtmlSanitizer
{
    private SymfonyHtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https', 'http'])
            ->allowMediaHosts($this->mediaHosts())
            // srcset tidak diperiksa sanitizer URL, sehingga bisa melewati daftar host gambar.
            ->dropAttribute('srcset', '*')
            ->dropAttribute('imagesrcset', '*')
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->forceHttpsUrls(app()->isProduction())
            ->withMaxInputLength(500_000);

        $this->sanitizer = new SymfonyHtmlSanitizer($config);
    }

    public function sanitize(?string $html): string
    {
        return $html === null ? '' : trim($this->sanitizer->sanitize($html));
    }

    /**
     * @return list<string>
     */
    private function mediaHosts(): array
    {
        $host = parse_url((string) config('filesystems.disks.s3.url'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? [$host] : [];
    }
}
