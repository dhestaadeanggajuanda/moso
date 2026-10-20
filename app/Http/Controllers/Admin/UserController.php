<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PasswordSetup;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\SsoIdentity;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Services\Sso\SsoConfig;
use App\Support\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Manajemen akun pegawai oleh Super Admin. Registrasi publik mati dan login
 * SSO tidak pernah membuat akun, jadi akun hanya lahir dari sini (atau seeder).
 *
 * Akun tidak dihapus, hanya dinonaktifkan, supaya artikel dan jejak audit
 * tetap menunjuk ke penulisnya. Kata sandi tidak pernah dikirim ke browser
 * atau dicatat di log.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $actor */
        $actor = $request->user();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(RoleName::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        return Inertia::render('admin/users/Index', [
            'users' => User::query()
                ->with('roles:id,name')
                ->withExists('ssoIdentities')
                ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->searchNameOrEmail($search))
                ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role)))
                ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->whereNull('deactivated_at'))
                ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->whereNotNull('deactivated_at'))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'is_active' => ! $user->isDeactivated(),
                    'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                    'sso_linked' => (bool) $user->getAttribute('sso_identities_exists'),
                    'is_self' => $user->is($actor),
                    'can_update' => $actor->can('update', $user),
                ]),
            'filters' => (object) $filters,
            'roles' => RoleName::options(),
            'can' => ['create' => $actor->can('create', User::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/Form', [
            'account' => null,
            'roles' => $this->roleOptions($request),
            'passwordSetups' => PasswordSetup::options(),
            'sso' => $this->ssoProps(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $setup = $request->enum('password_setup', PasswordSetup::class);
        $role = $request->enum('role', RoleName::class);

        $user = DB::transaction(function () use ($request, $setup, $role): User {
            $user = new User;
            $user->forceFill([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                // Tanpa kata sandi awal, akun diberi kata sandi acak yang tidak diketahui siapa pun.
                'password' => $setup === PasswordSetup::Manual
                    ? $request->string('password')->toString()
                    : Str::password(64),
                // Email ditetapkan Super Admin untuk pegawai dinas, bukan didaftarkan sendiri.
                'email_verified_at' => now(),
            ])->save();

            $user->assignRole($role);

            return $user;
        });

        AuditLog::record('user.created', [
            'user_id' => $user->id,
            'role' => $role?->value,
            'password_setup' => $setup?->value,
            'actor_id' => $request->user()?->id,
        ]);

        $message = 'Akun ditambahkan.';
        $type = 'success';

        if ($setup === PasswordSetup::Invitation) {
            try {
                $user->notify(new AccountCreated(Password::broker()->createToken($user)));
                $message = "Akun ditambahkan. Tautan atur kata sandi dikirim ke {$user->email}.";
            } catch (Throwable $e) {
                // Akun tetap dibuat; pegawai bisa memakai "Lupa kata sandi" setelah email pulih.
                report($e);
                $message = 'Akun ditambahkan, tetapi email undangan gagal dikirim. Minta pegawai memakai "Lupa kata sandi" di halaman login.';
                $type = 'warning';
            }
        }

        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return to_route('admin.users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('admin/users/Form', [
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles()->value('name'),
                'is_active' => ! $user->isDeactivated(),
                'deactivated_at' => $user->deactivated_at?->toIso8601String(),
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'is_self' => $user->is($request->user()),
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'sso_identities' => $user->ssoIdentities()
                    ->orderBy('provider')
                    ->get()
                    ->map(fn (SsoIdentity $identity) => [
                        'id' => $identity->id,
                        'provider' => $identity->provider,
                        'last_login_at' => $identity->last_login_at?->toIso8601String(),
                        'created_at' => $identity->created_at?->toIso8601String(),
                    ]),
            ],
            'roles' => $this->roleOptions($request),
            'passwordSetups' => [],
            'sso' => $this->ssoProps(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));
        $changed = array_keys($user->getDirty());
        $user->save();

        $role = $request->enum('role', RoleName::class);

        if ($role !== null && ! $user->hasExactRoles([$role->value])) {
            // Dicatat otomatis oleh LogPermissionChanges (permission.role_*).
            $user->syncRoles([$role]);
        }

        if ($changed !== []) {
            // Hanya nama kolom; nilai lama/baru (mis. email) tidak masuk log.
            AuditLog::record('user.updated', [
                'user_id' => $user->id,
                'fields' => implode(',', $changed),
                'actor_id' => $request->user()?->id,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Akun diperbarui.']);

        return to_route('admin.users.index');
    }

    /**
     * Akun nonaktif tidak bisa login (password, passkey, SSO) dan sesi yang
     * sedang berjalan berakhir pada permintaan berikutnya.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        // Super Admin melewati Policy, jadi larangan menonaktifkan diri sendiri dicek di sini.
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['account' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        if (! $user->isDeactivated()) {
            $user->forceFill(['deactivated_at' => now()])->save();

            AuditLog::record('user.deactivated', ['user_id' => $user->id, 'actor_id' => $request->user()?->id]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Akun {$user->name} dinonaktifkan."]);

        return back();
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        if ($user->isDeactivated()) {
            $user->forceFill(['deactivated_at' => null])->save();

            AuditLog::record('user.activated', ['user_id' => $user->id, 'actor_id' => $request->user()?->id]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Akun {$user->name} diaktifkan kembali."]);

        return back();
    }

    /**
     * Lepas tautan SSO, mis. bila akun IdP pegawai diganti. Login SSO berikutnya
     * menautkan ulang hanya bila email terverifikasi di IdP sama dengan email akun.
     */
    public function unlinkSso(Request $request, User $user, SsoIdentity $ssoIdentity): RedirectResponse
    {
        Gate::authorize('update', $user);

        $ssoIdentity->delete();

        AuditLog::record('sso.unlinked', [
            'user_id' => $user->id,
            'provider' => $ssoIdentity->provider,
            'actor_id' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tautan SSO dilepas.']);

        return to_route('admin.users.edit', $user);
    }

    /**
     * Peran yang boleh dipilih pengguna ini (Super Admin hanya untuk Super Admin).
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    private function roleOptions(Request $request): array
    {
        $canAssignSuperAdmin = $request->user()?->can('assignSuperAdmin', User::class) ?? false;

        return array_values(array_filter(
            RoleName::options(),
            fn (array $option) => $canAssignSuperAdmin || $option['value'] !== RoleName::SuperAdmin->value,
        ));
    }

    /**
     * @return array{enabled: bool, label: string, local_login: bool}
     */
    private function ssoProps(): array
    {
        return [
            'enabled' => SsoConfig::enabled(),
            'label' => SsoConfig::label(),
            'local_login' => SsoConfig::localLoginEnabled(),
        ];
    }
}
