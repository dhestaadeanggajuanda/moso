<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

it('rejects the correct password of a deactivated account with the generic message', function () {
    $user = User::factory()->deactivated()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai dengan data kami.']);

    $this->assertGuest();
});

it('ends an existing session on the next request after deactivation', function () {
    $user = User::factory()->create();
    $this->withSession([Auth::guard('web')->getName() => $user->id])->get(route('dashboard'))->assertOk();
    $user->forceFill(['deactivated_at' => now()])->save();
    // Guard menyimpan pengguna antar-permintaan dalam satu tes; di produksi setiap permintaan membaca ulang.
    Auth::forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('does not send a password reset link to a deactivated account', function () {
    Notification::fake();
    $user = User::factory()->deactivated()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();

    Notification::assertNotSentTo($user, ResetPassword::class);
});

it('refuses passkey login only for a deactivated account', function (User $user, bool $allowed) {
    $passkey = new Passkey;
    $passkey->setRelation('user', $user);

    expect(Passkeys::allowsLogin(Request::create('/passkeys/login', 'POST'), $passkey))->toBe($allowed);
})->with([
    'deactivated' => [fn () => User::factory()->deactivated()->create(), false],
    'active' => [fn () => User::factory()->create(), true],
]);
