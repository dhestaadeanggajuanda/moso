<?php

namespace App\Http\Responses;

use App\Services\Sso\OidcClient;
use App\Services\Sso\SsoConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesudah logout aplikasi, pengguna SSO diarahkan ke endpoint logout IdP agar
 * sesi SSO-nya ikut berakhir (RP-initiated logout). Pengguna lain mendapat
 * respons bawaan Fortify.
 *
 * Sesi sudah dihapus saat respons ini dibuat, jadi ID token dititipkan di
 * atribut request oleh App\Listeners\RememberSsoLogoutHint.
 */
class LogoutResponse implements LogoutResponseContract
{
    public const ID_TOKEN_ATTRIBUTE = 'sso_id_token';

    public function __construct(private OidcClient $client) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $idToken = $request->attributes->get(self::ID_TOKEN_ATTRIBUTE);

        if (SsoConfig::enabled() && is_string($idToken)) {
            $url = $this->client->endSessionUrl($idToken, route('home'));

            if ($url !== null) {
                return Inertia::location($url);
            }
        }

        return redirect(Fortify::redirects('logout', '/'));
    }
}
