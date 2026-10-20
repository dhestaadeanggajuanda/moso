<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Approver = 'Approver';
    case Editor = 'Editor';
    case Kontributor = 'Kontributor';

    /**
     * Ringkasan hak akses untuk formulir pengguna.
     */
    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Semua akses, termasuk pengaturan situs dan manajemen pengguna.',
            self::Approver => 'Menerbitkan artikel dan mengelola konten serta menu.',
            self::Editor => 'Meninjau artikel dan mengelola konten.',
            self::Kontributor => 'Menulis dan mengajukan artikel sendiri.',
        };
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => ['value' => $role->value, 'label' => $role->value, 'description' => $role->description()],
            self::cases(),
        );
    }
}
