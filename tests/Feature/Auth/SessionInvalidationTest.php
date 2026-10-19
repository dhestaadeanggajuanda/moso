<?php

use App\Models\User;

it('signs out a session elsewhere once the password changes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    // Kata sandi diganti dari perangkat lain (atau lewat reset kata sandi).
    $user->forceFill(['password' => 'KataSandiBaru-123'])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('keeps the current session signed in after changing the password there', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $this->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'KataSandiBaru-123',
            'password_confirmation' => 'KataSandiBaru-123',
        ])
        ->assertSessionHasNoErrors();

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($user);
});
