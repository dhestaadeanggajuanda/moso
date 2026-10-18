<?php

use App\Enums\RoleName;
use App\Models\User;

it('uses Indonesian as the application locale', function () {
    expect(app()->getLocale())->toBe('id');
});

it('shows validation messages in Indonesian with the form request attribute names', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.articles.store'), [])
        ->assertInvalid([
            'title' => 'Kolom judul wajib diisi.',
            'body' => 'Kolom isi artikel wajib diisi.',
        ]);
});

it('uses the global Indonesian attribute names for account fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => '',
            'email' => 'bukan-email',
        ])
        ->assertInvalid([
            'name' => 'Kolom nama wajib diisi.',
            'email' => 'Kolom alamat email harus berupa alamat email yang valid.',
        ]);
});

it('shows the failed login message in Indonesian', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'kata-sandi-salah',
    ])->assertInvalid([
        'email' => 'Email atau kata sandi tidak sesuai dengan data kami.',
    ]);
});
