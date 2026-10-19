<?php

namespace App\Listeners;

use App\Http\Responses\LogoutResponse;
use Illuminate\Auth\Events\Logout;

/**
 * Event Logout terjadi sebelum sesi dihapus. ID token SSO disalin ke atribut
 * request supaya LogoutResponse masih bisa mengakhiri sesi di IdP.
 */
class RememberSsoLogoutHint
{
    public function handle(Logout $event): void
    {
        $request = request();

        if (! $request->hasSession()) {
            return;
        }

        $idToken = $request->session()->get('sso.id_token');

        if (is_string($idToken)) {
            $request->attributes->set(LogoutResponse::ID_TOKEN_ATTRIBUTE, $idToken);
        }
    }
}
