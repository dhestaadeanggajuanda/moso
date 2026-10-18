<?php

namespace App\Listeners;

use App\Support\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

/**
 * Jejak audit autentikasi (OWASP A09). Hanya ID pengguna dan email yang dicatat;
 * password dan token tidak pernah masuk log.
 */
class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        AuditLog::record('auth.login', ['user_id' => $this->id($event->user), 'guard' => $event->guard]);
    }

    public function handleLogout(Logout $event): void
    {
        AuditLog::record('auth.logout', ['user_id' => $this->id($event->user), 'guard' => $event->guard]);
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        AuditLog::record('auth.failed', [
            'email' => is_string($email) ? mb_strtolower($email) : null,
            'user_id' => $this->id($event->user),
            'guard' => $event->guard,
        ]);
    }

    public function handleLockout(Lockout $event): void
    {
        $email = $event->request->input('email');

        AuditLog::record('auth.lockout', ['email' => is_string($email) ? mb_strtolower($email) : null]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        AuditLog::record('auth.password_reset', ['user_id' => $this->id($event->user)]);
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        AuditLog::record('auth.two_factor_enabled', ['user_id' => $this->id($event->user)]);
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        AuditLog::record('auth.two_factor_disabled', ['user_id' => $this->id($event->user)]);
    }

    private function id(mixed $user): int|string|null
    {
        if (! $user instanceof Authenticatable) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }
}
