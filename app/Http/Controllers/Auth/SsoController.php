<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SsoAuthenticationFailed;
use App\Http\Controllers\Controller;
use App\Services\Sso\OidcClient;
use App\Services\Sso\SsoAccountLinker;
use App\Services\Sso\SsoConfig;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login lewat SSO OpenID Connect. Lihat docs/SSO-ROADMAP.md.
 *
 * Gagal berarti tertutup: setiap kegagalan (state, token, klaim, akun) berakhir
 * di halaman login dengan pesan umum dan dicatat di log audit tanpa token.
 */
class SsoController extends Controller
{
    public function redirect(Request $request, OidcClient $client): RedirectResponse
    {
        abort_unless(SsoConfig::enabled(), 404);

        try {
            $authorization = $client->authorizationRequest();
        } catch (SsoAuthenticationFailed $e) {
            return $this->fail($e);
        }

        $request->session()->put('sso.pending', [
            'state' => $authorization['state'],
            'nonce' => $authorization['nonce'],
            'code_verifier' => $authorization['code_verifier'],
        ]);

        return redirect()->away($authorization['url']);
    }

    public function callback(Request $request, OidcClient $client, SsoAccountLinker $linker): RedirectResponse
    {
        abort_unless(SsoConfig::enabled(), 404);

        // Sekali pakai: nilai sesi dihapus walau callback gagal, agar tidak bisa diputar ulang.
        $pending = $request->session()->pull('sso.pending');

        try {
            if ($request->filled('error')) {
                throw SsoAuthenticationFailed::because($request->query('error') === 'access_denied' ? 'access_denied' : 'idp_error');
            }

            $state = $request->query('state');
            $code = $request->query('code');

            if (
                ! is_array($pending)
                || ! is_string($state) || ! hash_equals((string) $pending['state'], $state)
                || ! is_string($code) || $code === ''
            ) {
                throw SsoAuthenticationFailed::because('state_mismatch');
            }

            $identity = $client->authenticate($code, (string) $pending['code_verifier'], (string) $pending['nonce']);
            $link = $linker->resolve($identity);
        } catch (SsoAuthenticationFailed $e) {
            return $this->fail($e);
        }

        $user = $link->user;
        $link->forceFill(['last_login_at' => now()])->save();
        $context = ['user_id' => $user->id, 'provider' => $link->provider, 'mfa' => $identity->mfa];

        // Untuk RP-initiated logout dan pengecualian 2FA. Ikut terbawa saat sesi diregenerasi.
        $request->session()->put(['sso.id_token' => $identity->idToken, 'sso.mfa' => $identity->mfa]);

        // 2FA aplikasi yang sudah aktif tetap diminta bila IdP tidak membuktikan MFA.
        if ($user->two_factor_confirmed_at !== null && ! $identity->mfa) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);
            AuditLog::record('sso.authenticated', [...$context, 'second_factor' => 'required']);

            return to_route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('sso.login', $context);

        return redirect()->intended(config('fortify.home'));
    }

    private function fail(SsoAuthenticationFailed $e): RedirectResponse
    {
        AuditLog::record('sso.failed', ['reason' => $e->reason]);

        if ($e->getPrevious() !== null) {
            report($e);
        }

        return to_route('login')->withErrors(['sso' => $e->userMessage()]);
    }
}
