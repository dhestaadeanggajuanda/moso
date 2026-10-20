<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthentication();
        $this->configureAuthorization();
        $this->configureRateLimiting();
    }

    /**
     * Akun yang dinonaktifkan Super Admin diperlakukan seperti tidak ada oleh
     * guard dan password broker (driver `active-users` di config/auth.php):
     * login kata sandi dan "ingat saya" ditolak dengan pesan umum, sesi yang
     * berjalan berakhir pada permintaan berikutnya, dan tautan reset tidak dikirim.
     * Passkey tidak lewat user provider, jadi dicek terpisah. SSO: SsoAccountLinker.
     */
    protected function configureAuthentication(): void
    {
        Auth::provider('active-users', fn (Application $app, array $config) => (new EloquentUserProvider($app->make('hash'), $config['model']))
            ->withQuery(fn (Builder $query) => $query->whereNull('deactivated_at')));

        Passkeys::authorizeLoginUsing(fn (Request $request, PasskeyUser $user) => $user instanceof User && ! $user->isDeactivated());
    }

    /**
     * Batas permintaan untuk endpoint publik tanpa autentikasi (OWASP A06).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('downloads', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('sso', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }

    /**
     * Super Admin melewati semua pengecekan Gate/Policy. Mengembalikan null
     * (bukan false) supaya pengguna lain tetap dicek oleh Policy masing-masing.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
