<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi permintaan tautan reset dan penyimpanan kata sandi baru (OWASP A07).
 *
 * Route reset milik Fortify tidak punya limiter bawaan, jadi middleware ini
 * dipasang di grup middleware Fortify (config/fortify.php) dan hanya aktif
 * untuk POST `password.email` dan `password.update`, dengan limiter
 * `password-reset` dari FortifyServiceProvider.
 */
class ThrottlePasswordResets
{
    public const LIMITER = 'password-reset';

    public function __construct(private ThrottleRequests $throttle) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->routeIs('password.email', 'password.update')) {
            return $this->throttle->handle($request, $next, self::LIMITER);
        }

        return $next($request);
    }
}
