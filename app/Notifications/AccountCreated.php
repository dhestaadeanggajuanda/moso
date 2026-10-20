<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * Undangan untuk akun yang dibuat Super Admin: tautan atur kata sandi dari
 * password broker Laravel, sehingga kata sandi tidak pernah dikirim lewat email.
 */
class AccountCreated extends Notification
{
    use Queueable;

    public function __construct(#[SensitiveParameter] public string $token) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable instanceof User ? $notifiable->email : '';
        $expires = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
        $appName = (string) config('app.name');

        return (new MailMessage)
            ->subject("Akun {$appName} untuk Anda")
            ->greeting('Halo,')
            ->line("Akun Anda di panel {$appName} sudah dibuat dengan email {$email}.")
            ->line('Atur kata sandi Anda lewat tombol di bawah untuk mulai masuk.')
            ->action('Atur kata sandi', route('password.reset', ['token' => $this->token, 'email' => $email]))
            ->line("Tautan ini berlaku {$expires} menit. Setelah itu, minta tautan baru lewat \"Lupa kata sandi\" di halaman login.")
            ->line('Bila Anda tidak merasa membutuhkan akun ini, abaikan email ini.')
            ->salutation("Salam,\n{$appName}");
    }
}
