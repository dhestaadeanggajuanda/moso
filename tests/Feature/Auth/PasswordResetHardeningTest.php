<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('answers the same way whether or not the email has an account', function () {
    $user = User::factory()->create();

    $registered = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email]);
    $unknown = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'tidak-terdaftar@example.test']);

    foreach ([$registered, $unknown] as $response) {
        $response->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));
    }

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

it('does not reveal that a registered email is being throttled by the broker', function () {
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    // Permintaan kedua dalam 60 detik ditolak broker (RESET_THROTTLED), tapi jawabannya tetap sama.
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('passwords.sent'));
});

it('limits reset link requests for one email address', function () {
    foreach (range(1, 3) as $attempt) {
        $this->post(route('password.email'), ['email' => 'korban@example.test'])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'korban@example.test'])->assertTooManyRequests();
});

it('limits reset link requests from one address across many emails', function () {
    foreach (range(1, 10) as $attempt) {
        $this->post(route('password.email'), ['email' => "tebak{$attempt}@example.test"])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'tebak11@example.test'])->assertTooManyRequests();
});

it('limits attempts to set a new password with a guessed token', function () {
    $user = User::factory()->create();
    $attempt = fn () => $this->post(route('password.update'), [
        'token' => 'tebakan',
        'email' => $user->email,
        'password' => 'KataSandiBaru-123',
        'password_confirmation' => 'KataSandiBaru-123',
    ]);

    foreach (range(1, 3) as $try) {
        $attempt()->assertSessionHasErrors('email');
    }

    $attempt()->assertTooManyRequests();
});
