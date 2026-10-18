<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->audit = Mockery::spy(LoggerInterface::class);
    Log::partialMock()->shouldReceive('channel')->with('audit')->andReturn($this->audit);
});

it('records successful logins by user id', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->audit->shouldHaveReceived('info')->with('auth.login', Mockery::on(fn (array $context) => $context['user_id'] === $user->id));
});

it('records failed logins without the password', function () {
    $user = User::factory()->create(['email' => 'pegawai@dinas.test']);

    $this->post(route('login.store'), ['email' => 'Pegawai@Dinas.test', 'password' => 'salah-sekali']);

    $this->audit->shouldHaveReceived('info')->with('auth.failed', Mockery::on(fn (array $context) => $context['email'] === 'pegawai@dinas.test'
        && $context['user_id'] === $user->id
        && ! array_key_exists('password', $context)
        && ! in_array('salah-sekali', $context, true)));
});

it('records role assignments with the role names', function () {
    $user = userWithRole(RoleName::Editor);

    $this->audit->shouldHaveReceived('info')->with('permission.role_attached', Mockery::on(fn (array $context) => $context['subject_type'] === 'User'
        && $context['subject_id'] === $user->id
        && $context['names'] === RoleName::Editor->value));
});
