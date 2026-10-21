<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Fortify\Features;

/**
 * Daftar periksa konfigurasi produksi (OWASP A02 Security Misconfiguration).
 *
 * Jalankan di server sesudah deploy (`php artisan security:check`). Keluar
 * dengan kode gagal bila ada pemeriksaan wajib yang tidak lolos, sehingga
 * bisa dipakai sebagai gerbang di skrip deploy.
 */
#[Signature('security:check')]
#[Description('Periksa konfigurasi keamanan untuk produksi (debug, HTTPS, cookie, CSP, HSTS, 2FA)')]
class SecurityCheck extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $failures = 0;

        foreach ($this->checks() as ['label' => $label, 'passed' => $passed, 'required' => $required, 'hint' => $hint]) {
            $status = match (true) {
                $passed => '<fg=green;options=bold>OK</>',
                $required => '<fg=red;options=bold>GAGAL</>',
                default => '<fg=yellow;options=bold>PERIKSA</>',
            };

            $this->components->twoColumnDetail($passed ? $label : "{$label} <fg=gray>({$hint})</>", $status);

            if (! $passed && $required) {
                $failures++;
            }
        }

        $this->newLine();

        if ($failures > 0) {
            $this->components->error("{$failures} pemeriksaan wajib gagal. Perbaiki .env sebelum situs dibuka untuk publik.");

            return self::FAILURE;
        }

        $this->components->info('Semua pemeriksaan wajib lolos.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{label: string, passed: bool, required: bool, hint: string}>
     */
    private function checks(): array
    {
        $logLevel = strtolower((string) config('logging.channels.'.config('logging.default').'.level', 'debug'));

        return [
            $this->check('APP_ENV production', app()->isProduction(), true, 'APP_ENV=production'),
            $this->check('APP_DEBUG mati', config('app.debug') === false, true, 'APP_DEBUG=false, agar stack trace tidak tampil'),
            $this->check('APP_KEY terisi', filled(config('app.key')), true, 'php artisan key:generate'),
            $this->check('APP_URL memakai HTTPS', str_starts_with((string) config('app.url'), 'https://'), true, 'APP_URL=https://...'),
            $this->check('Cookie sesi hanya lewat HTTPS', config('session.secure') === true, true, 'SESSION_SECURE_COOKIE=true'),
            $this->check('Sesi terenkripsi', config('session.encrypt') === true, true, 'SESSION_ENCRYPT=true'),
            $this->check('Content-Security-Policy aktif dan memblokir', config('security.csp.enabled') === true && config('security.csp.report_only') === false, true, 'CSP_ENABLED=true, CSP_REPORT_ONLY=false'),
            $this->check('HSTS aktif', config('security.hsts.enabled') === true, true, 'HSTS_ENABLED=true'),
            $this->check('2FA wajib untuk Super Admin dan Approver', config('security.two_factor.enforce') === true, true, 'SECURITY_ENFORCE_2FA=true'),
            $this->check('Registrasi publik mati', ! Features::enabled(Features::registration()), true, 'hapus Features::registration() di config/fortify.php'),
            $this->check('Log tidak di level debug', $logLevel !== 'debug', false, 'LOG_LEVEL=warning atau error'),
            $this->check('Proxy tepercaya diatur', filled(config('trustedproxy.proxies')), false, 'TRUSTED_PROXIES, abaikan bila tanpa reverse proxy'),
            ...$this->ssoChecks(),
            ...$this->agenticChecks(),
        ];
    }

    /**
     * Pemeriksaan asisten AI (moso-agentic), hanya bila AGENTIC_ENABLED=true.
     *
     * Token dinas dikirim di setiap permintaan, jadi koneksi wajib HTTPS kecuali
     * service berada di jaringan privat yang sama (IP privat atau nama container).
     *
     * @return list<array{label: string, passed: bool, required: bool, hint: string}>
     */
    private function agenticChecks(): array
    {
        if (! config('agentic.enabled')) {
            return [];
        }

        $url = (string) config('agentic.base_url');
        $secureTransport = str_starts_with($url, 'https://')
            || (str_starts_with($url, 'http://') && $this->isPrivateNetworkHost(parse_url($url, PHP_URL_HOST)));

        return [
            $this->check('Asisten AI: token terisi', filled(config('agentic.token')), true, 'AGENTIC_TOKEN dari tenant:create di server agentic'),
            $this->check('Asisten AI: HTTPS atau jaringan privat', $secureTransport, true, 'AGENTIC_URL=https://..., atau http:// hanya ke IP privat'),
        ];
    }

    /**
     * IP privat/khusus (10.x, 172.16-31.x, 192.168.x, loopback) atau nama host
     * satu label seperti nama service Docker ("agentic").
     */
    private function isPrivateNetworkHost(mixed $host): bool
    {
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return $host === 'localhost' || ! str_contains($host, '.');
    }

    /**
     * Pemeriksaan SSO, hanya bila SSO_ENABLED=true.
     *
     * @return list<array{label: string, passed: bool, required: bool, hint: string}>
     */
    private function ssoChecks(): array
    {
        if (! config('sso.enabled')) {
            return [];
        }

        return [
            $this->check('SSO: issuer memakai HTTPS', str_starts_with((string) config('sso.issuer'), 'https://'), true, 'SSO_ISSUER=https://...'),
            $this->check('SSO: client id dan secret terisi', filled(config('sso.client_id')) && filled(config('sso.client_secret')), true, 'SSO_CLIENT_ID, SSO_CLIENT_SECRET'),
        ];
    }

    /**
     * @return array{label: string, passed: bool, required: bool, hint: string}
     */
    private function check(string $label, bool $passed, bool $required, string $hint): array
    {
        return ['label' => $label, 'passed' => $passed, 'required' => $required, 'hint' => $hint];
    }
}
