<?php

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\SsoIdentity;
use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

/**
 * Tangkap entri channel `audit` untuk diperiksa sesudah permintaan.
 */
function spyUserAudit(): MockInterface
{
    $audit = Mockery::spy(LoggerInterface::class);
    Log::partialMock()->shouldReceive('channel')->with('audit')->andReturn($audit);

    return $audit;
}

describe('authorization', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    });

    it('forbids an editor from every user management endpoint', function (string $method, Closure $url) {
        $target = User::factory()->create();
        $identity = SsoIdentity::factory()->for($target)->create();

        $this->actingAs(userWithRole(RoleName::Editor))
            ->call($method, $url($target, $identity), [
                'name' => 'Penyusup',
                'email' => 'penyusup@dinas.test',
                'role' => RoleName::SuperAdmin->value,
                'password_setup' => 'invitation',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@dinas.test']);
        $this->assertModelExists($identity);
        expect($target->refresh()->deactivated_at)->toBeNull();
    })->with([
        'index' => ['GET', fn () => route('admin.users.index')],
        'create' => ['GET', fn () => route('admin.users.create')],
        'store' => ['POST', fn () => route('admin.users.store')],
        'edit' => ['GET', fn (User $user) => route('admin.users.edit', $user)],
        'update' => ['PUT', fn (User $user) => route('admin.users.update', $user)],
        'deactivate' => ['POST', fn (User $user) => route('admin.users.deactivate', $user)],
        'activate' => ['POST', fn (User $user) => route('admin.users.activate', $user)],
        'unlink sso' => ['DELETE', fn (User $user, SsoIdentity $identity) => route('admin.users.sso-identities.destroy', [$user, $identity])],
    ]);
});

describe('index', function () {
    it('lists accounts with role, status, and sso link without password data', function () {
        $admin = userWithRole(RoleName::SuperAdmin, ['name' => 'Admin Dinas']);
        $kontributor = userWithRole(RoleName::Kontributor, ['name' => 'Budi Penulis']);
        $kontributor->forceFill(['deactivated_at' => now()])->save();
        SsoIdentity::factory()->for($kontributor)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('users.data', 2)
                ->where('users.data.0.is_self', true)
                ->has('users.data.1', fn (Assert $row) => $row
                    ->where('name', 'Budi Penulis')
                    ->where('role', RoleName::Kontributor->value)
                    ->where('is_active', false)
                    ->where('sso_linked', true)
                    ->missing('password')
                    ->etc()));
    });

    it('searches names and emails literally', function () {
        $admin = userWithRole(RoleName::SuperAdmin, ['name' => 'Admin Dinas', 'email' => 'admin@dinas.test']);
        userWithRole(RoleName::Editor, ['name' => 'Target 100% Hadir', 'email' => 'target@dinas.test']);
        userWithRole(RoleName::Editor, ['name' => 'Target 1000 Hadir', 'email' => 'lain@dinas.test']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => '100%']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', 'target@dinas.test'));
    });

    it('filters by role and status', function () {
        $admin = userWithRole(RoleName::SuperAdmin);
        userWithRole(RoleName::Editor, ['email' => 'aktif@dinas.test']);
        userWithRole(RoleName::Editor, ['email' => 'nonaktif@dinas.test', 'deactivated_at' => now()]);
        userWithRole(RoleName::Kontributor, ['deactivated_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => RoleName::Editor->value, 'status' => 'inactive']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', 'nonaktif@dinas.test'));
    });
});

describe('store', function () {
    it('creates a verified account with an initial password and never logs it', function () {
        Notification::fake();
        $audit = spyUserAudit();
        $admin = userWithRole(RoleName::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Siti Aminah',
                'email' => ' Siti.Aminah@Dinas.test ',
                'role' => RoleName::Kontributor->value,
                'password_setup' => 'manual',
                'password' => 'rahasia-awal-123',
                'password_confirmation' => 'rahasia-awal-123',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertInertiaFlash('toast.type', 'success');

        $user = User::query()->where('email', 'siti.aminah@dinas.test')->sole();
        expect($user->hasExactRoles([RoleName::Kontributor->value]))->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('rahasia-awal-123', $user->password))->toBeTrue();
        Notification::assertNothingSent();
        $audit->shouldHaveReceived('info')->with('user.created', Mockery::on(fn (array $context) => $context['user_id'] === $user->id
            && $context['actor_id'] === $admin->id
            && $context['role'] === RoleName::Kontributor->value
            && ! in_array('rahasia-awal-123', $context, true)));
    });

    it('emails a set-password link instead of choosing a password', function () {
        Notification::fake([AccountCreated::class]);

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->post(route('admin.users.store'), [
                'name' => 'Rudi',
                'email' => 'rudi@dinas.test',
                'role' => RoleName::Editor->value,
                'password_setup' => 'invitation',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'rudi@dinas.test')->sole();
        Notification::assertSentTo($user, AccountCreated::class, fn (AccountCreated $notification) => Password::broker()->tokenExists($user, $notification->token));
    });

    it('keeps the account and warns when the invitation email fails', function () {
        Exceptions::fake();
        $this->mock(Dispatcher::class, fn (MockInterface $mock) => $mock->shouldReceive('send')->andThrow(new RuntimeException('SMTP tidak tersedia')));

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->post(route('admin.users.store'), [
                'name' => 'Rudi',
                'email' => 'rudi@dinas.test',
                'role' => RoleName::Editor->value,
                'password_setup' => 'invitation',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertInertiaFlash('toast.type', 'warning');

        $this->assertDatabaseHas('users', ['email' => 'rudi@dinas.test']);
        Exceptions::assertReported(RuntimeException::class);
    });

    it('requires name, email, role, and initial access', function () {
        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->post(route('admin.users.store'), [])
            ->assertInvalid([
                'name' => 'Kolom nama wajib diisi.',
                'email' => 'Kolom alamat email wajib diisi.',
                'role' => 'Kolom peran wajib diisi.',
                'password_setup' => 'Kolom akses awal wajib diisi.',
            ]);
    });

    it('rejects an email that is already used in any letter case', function () {
        User::factory()->create(['email' => 'pegawai@dinas.test']);

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->post(route('admin.users.store'), [
                'name' => 'Pegawai Kedua',
                'email' => 'Pegawai@Dinas.TEST',
                'role' => RoleName::Kontributor->value,
                'password_setup' => 'invitation',
            ])
            ->assertInvalid(['email' => 'Alamat email sudah digunakan.']);

        expect(User::query()->where('name', 'Pegawai Kedua')->exists())->toBeFalse();
    });

    it('requires a confirmed password when setting one manually', function () {
        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->post(route('admin.users.store'), [
                'name' => 'Rudi',
                'email' => 'rudi@dinas.test',
                'role' => RoleName::Editor->value,
                'password_setup' => 'manual',
                'password' => 'rahasia-awal-123',
            ])
            ->assertInvalid(['password' => 'Konfirmasi kata sandi tidak cocok.']);
    });

    it('rejects sso-only accounts while sso is disabled', function () {
        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->post(route('admin.users.store'), [
                'name' => 'Rudi',
                'email' => 'rudi@dinas.test',
                'role' => RoleName::Editor->value,
                'password_setup' => 'sso_only',
            ])
            ->assertInvalid(['password_setup' => 'Pilihan akses awal tidak valid.']);
    });

    it('does not let a non super admin manager grant the super admin role', function () {
        $manager = userWithRole(RoleName::Editor);
        $manager->givePermissionTo(PermissionName::UsersManage->value);

        $this->actingAs($manager)
            ->post(route('admin.users.store'), [
                'name' => 'Calon Admin',
                'email' => 'calon@dinas.test',
                'role' => RoleName::SuperAdmin->value,
                'password_setup' => 'invitation',
            ])
            ->assertInvalid(['role' => 'Pilihan peran tidak valid.']);

        $this->assertDatabaseMissing('users', ['email' => 'calon@dinas.test']);
    });
});

describe('edit', function () {
    it('shows linked sso identities without the identity subject', function () {
        $user = userWithRole(RoleName::Editor);
        SsoIdentity::factory()->for($user)->create(['subject' => 'subjek-rahasia']);

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Form')
                ->where('account.role', RoleName::Editor->value)
                ->has('account.sso_identities', 1, fn (Assert $identity) => $identity
                    ->where('provider', config('sso.provider'))
                    ->missing('subject')
                    ->etc())
                ->missing('account.password'));
    });
});

describe('update', function () {
    it('updates name, email, and role and audits only the changed field names', function () {
        $audit = spyUserAudit();
        $admin = userWithRole(RoleName::SuperAdmin);
        $user = userWithRole(RoleName::Kontributor, ['name' => 'Nama Lama', 'email' => 'lama@dinas.test']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Baru',
                'email' => 'Baru@Dinas.test',
                'role' => RoleName::Editor->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        expect($user->name)->toBe('Nama Baru')
            ->and($user->email)->toBe('baru@dinas.test')
            ->and($user->hasExactRoles([RoleName::Editor->value]))->toBeTrue();
        $audit->shouldHaveReceived('info')->with('user.updated', Mockery::on(fn (array $context) => $context['user_id'] === $user->id
            && $context['fields'] === 'name,email'
            && ! in_array('baru@dinas.test', $context, true)));
    });

    it('refuses to change the own role', function () {
        $admin = userWithRole(RoleName::SuperAdmin);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => RoleName::Editor->value,
            ])
            ->assertInvalid(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);

        expect($admin->refresh()->isSuperAdmin())->toBeTrue();
    });

    it('lets a super admin rename the own account while keeping the role', function () {
        $admin = userWithRole(RoleName::SuperAdmin);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Nama Baru',
                'email' => $admin->email,
                'role' => RoleName::SuperAdmin->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        expect($admin->refresh()->name)->toBe('Nama Baru');
    });
});

describe('deactivate and activate', function () {
    it('deactivates another account and audits it', function () {
        $audit = spyUserAudit();
        $admin = userWithRole(RoleName::SuperAdmin);
        $user = userWithRole(RoleName::Editor);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.deactivate', $user))
            ->assertRedirect(route('admin.users.index'));

        expect($user->refresh()->isDeactivated())->toBeTrue();
        $audit->shouldHaveReceived('info')->with('user.deactivated', Mockery::on(fn (array $context) => $context['user_id'] === $user->id
            && $context['actor_id'] === $admin->id));
    });

    it('refuses to deactivate the own account', function () {
        $admin = userWithRole(RoleName::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $admin))
            ->assertInvalid(['account' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);

        expect($admin->refresh()->isDeactivated())->toBeFalse();
    });

    it('reactivates a deactivated account and audits it', function () {
        $audit = spyUserAudit();
        $user = userWithRole(RoleName::Editor, ['deactivated_at' => now()]);

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->from(route('admin.users.index'))
            ->post(route('admin.users.activate', $user))
            ->assertRedirect(route('admin.users.index'));

        expect($user->refresh()->isDeactivated())->toBeFalse();
        $audit->shouldHaveReceived('info')->with('user.activated', Mockery::on(fn (array $context) => $context['user_id'] === $user->id));
    });
});

describe('unlink sso', function () {
    it('removes the sso identity and audits it', function () {
        $audit = spyUserAudit();
        $user = userWithRole(RoleName::Editor);
        $identity = SsoIdentity::factory()->for($user)->create();

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->delete(route('admin.users.sso-identities.destroy', [$user, $identity]))
            ->assertRedirect(route('admin.users.edit', $user));

        $this->assertModelMissing($identity);
        $audit->shouldHaveReceived('info')->with('sso.unlinked', Mockery::on(fn (array $context) => $context['user_id'] === $user->id
            && $context['provider'] === $identity->provider));
    });

    it('returns 404 for an identity that belongs to another account', function () {
        $user = userWithRole(RoleName::Editor);
        $otherIdentity = SsoIdentity::factory()->create();

        $this->actingAs(userWithRole(RoleName::SuperAdmin))
            ->delete(route('admin.users.sso-identities.destroy', [$user, $otherIdentity]))
            ->assertNotFound();

        $this->assertModelExists($otherIdentity);
    });
});
