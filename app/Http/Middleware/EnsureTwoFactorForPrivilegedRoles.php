<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role berhak tinggi (Super Admin, Approver) wajib mengaktifkan 2FA sebelum
 * memakai panel admin (OWASP A07). Pengguna diarahkan ke halaman keamanan.
 *
 * Pengecualian: sesi login SSO yang ID token-nya membuktikan MFA di IdP
 * (`sso.mfa`, hanya diisi SsoController dari klaim yang sudah divalidasi).
 */
class EnsureTwoFactorForPrivilegedRoles
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            config('security.two_factor.enforce')
            && $user instanceof User
            && $user->hasAnyRole(config('security.two_factor.roles'))
            && $user->two_factor_confirmed_at === null
            && $request->session()->get('sso.mfa') !== true
        ) {
            return redirect()->route('security.edit')
                ->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
