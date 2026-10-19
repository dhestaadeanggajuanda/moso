<?php

namespace App\Http\Middleware;

use App\Services\Sso\SsoConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Matikan login lokal (password, passkey, reset password) bila
 * SSO_LOCAL_LOGIN=false, sehingga semua pegawai masuk lewat SSO dan akses
 * berakhir begitu akunnya dinonaktifkan di IdP. Dipasang di grup middleware
 * Fortify (config/fortify.php); tidak berlaku selama SSO belum aktif.
 */
class EnsureLocalLoginEnabled
{
    /**
     * @var list<string>
     */
    private const LOCAL_LOGIN_ROUTES = [
        'login.store',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'passkey.login-options',
        'passkey.login',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(! SsoConfig::localLoginEnabled() && $request->routeIs(...self::LOCAL_LOGIN_ROUTES), 404);

        return $next($request);
    }
}
