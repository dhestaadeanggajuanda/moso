<?php

namespace App\Services\Sso;

use App\Exceptions\SsoAuthenticationFailed;
use App\Models\SsoIdentity;
use App\Models\User;
use App\Support\AuditLog;

/**
 * Mencocokkan identitas SSO dengan akun portal. Akun tidak pernah dibuat
 * otomatis dari IdP (OWASP A07).
 *
 * 1. Identitas yang sudah tertaut dicari lewat (provider, subject).
 * 2. Bila belum, ditautkan hanya kalau IdP menyatakan email terverifikasi
 *    dan email itu milik akun yang belum tertaut ke subject lain.
 */
class SsoAccountLinker
{
    /**
     * @throws SsoAuthenticationFailed
     */
    public function resolve(OidcIdentity $identity): SsoIdentity
    {
        $provider = SsoConfig::provider();

        $linked = SsoIdentity::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('subject', $identity->subject)
            ->first();

        if ($linked !== null) {
            if ($linked->user->isDeactivated()) {
                throw SsoAuthenticationFailed::because('account_deactivated');
            }

            return $linked;
        }

        if ($identity->email === null) {
            throw SsoAuthenticationFailed::because('not_registered');
        }

        if (! $identity->emailVerified) {
            throw SsoAuthenticationFailed::because('email_unverified');
        }

        $user = User::query()->whereRaw('lower(email) = ?', [mb_strtolower($identity->email)])->first();

        if ($user === null) {
            throw SsoAuthenticationFailed::because('not_registered');
        }

        if ($user->isDeactivated()) {
            throw SsoAuthenticationFailed::because('account_deactivated');
        }

        // Akun sudah tertaut ke subject lain (mis. email dipakai ulang di IdP): jangan diambil alih.
        if ($user->ssoIdentities()->where('provider', $provider)->exists()) {
            throw SsoAuthenticationFailed::because('already_linked');
        }

        $link = new SsoIdentity;
        $link->user_id = $user->id;
        $link->provider = $provider;
        $link->subject = $identity->subject;
        $link->save();
        $link->setRelation('user', $user);

        AuditLog::record('sso.linked', ['user_id' => $user->id, 'provider' => $provider]);

        return $link;
    }
}
