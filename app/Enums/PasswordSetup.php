<?php

namespace App\Enums;

use App\Services\Sso\SsoConfig;

/**
 * Cara pegawai mendapatkan akses pertama kali ke akun yang dibuat Super Admin.
 */
enum PasswordSetup: string
{
    case Invitation = 'invitation';
    case Manual = 'manual';
    case SsoOnly = 'sso_only';

    public function label(): string
    {
        return match ($this) {
            self::Invitation => 'Kirim tautan atur kata sandi ke email',
            self::Manual => 'Tetapkan kata sandi awal',
            self::SsoOnly => 'Tanpa kata sandi, masuk lewat SSO',
        };
    }

    /**
     * Pilihan yang berlaku dengan konfigurasi login saat ini. Tautan dan kata
     * sandi tidak berguna bila login lokal dimatikan; SSO hanya bila aktif.
     *
     * @return list<self>
     */
    public static function available(): array
    {
        return array_values(array_filter([
            SsoConfig::localLoginEnabled() ? self::Invitation : null,
            SsoConfig::localLoginEnabled() ? self::Manual : null,
            SsoConfig::enabled() ? self::SsoOnly : null,
        ]));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $setup) => ['value' => $setup->value, 'label' => $setup->label()],
            self::available(),
        );
    }
}
