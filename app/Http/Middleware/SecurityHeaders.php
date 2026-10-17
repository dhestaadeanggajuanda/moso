<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan header keamanan dan Content-Security-Policy berbasis nonce ke setiap respons.
 *
 * Script hanya boleh berasal dari origin sendiri atau tag yang membawa nonce
 * (Vite::useCspNonce() menempelkan nonce ke tag @vite secara otomatis).
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (config('security.hsts.enabled') && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.config('security.hsts.max_age').'; includeSubDomains',
            );
        }

        if (config('security.csp.enabled')) {
            $response->headers->set(
                config('security.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
                $this->contentSecurityPolicy($request),
            );
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $storage = $this->origin(config('filesystems.disks.s3.url'));
        [$viteHttp, $viteWs] = $this->viteDevServerOrigins();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-".Vite::cspNonce()."'", $viteHttp],
            // Inline style tetap diizinkan: komponen UI (reka-ui) dan progress bar Inertia menyuntikkan style.
            // Jangan menambah nonce di sini, karena nonce membuat browser mengabaikan 'unsafe-inline'.
            'style-src' => ["'self'", "'unsafe-inline'", $viteHttp],
            'img-src' => ["'self'", 'data:', 'blob:', $storage, ...config('security.csp.img_src')],
            'font-src' => ["'self'", 'data:', $viteHttp],
            'connect-src' => ["'self'", $viteHttp, $viteWs, ...config('security.csp.connect_src')],
            'media-src' => ["'self'", $storage, ...config('security.csp.media_src')],
            'frame-src' => ["'self'", ...config('security.csp.frame_src')],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', array_unique(array_filter($sources))))
            ->values();

        if ($request->isSecure()) {
            $policy->push('upgrade-insecure-requests');
        }

        return $policy->implode('; ');
    }

    /**
     * Origin Vite dev server (HTTP dan WebSocket HMR), hanya saat `npm run dev` berjalan.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function viteDevServerOrigins(): array
    {
        if (app()->isProduction() || ! Vite::isRunningHot()) {
            return [null, null];
        }

        $origin = $this->origin(trim((string) file_get_contents(Vite::hotFile())));

        return [$origin, $origin ? preg_replace('#^http#', 'ws', $origin) : null];
    }

    private function origin(?string $url): ?string
    {
        $parts = $url ? parse_url($url) : false;

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $host = str_contains($parts['host'], ':') && ! str_starts_with($parts['host'], '[')
            ? '['.$parts['host'].']'
            : $parts['host'];

        return $parts['scheme'].'://'.$host.(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
