<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Approver = 'Approver';
    case Editor = 'Editor';
    case Penulis = 'Penulis';
    case Kontributor = 'Kontributor';

    /**
     * Ringkasan hak akses untuk formulir pengguna.
     */
    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Semua akses, termasuk pengaturan situs dan manajemen pengguna.',
            self::Approver => 'Menerbitkan dan mengarsipkan semua artikel, mengelola konten dan menu. Di alur Bertahap: penerbit akhir.',
            self::Editor => 'Admin harian situs: menulis dan menerbitkan semua artikel, mengelola halaman, menu, dan konten lain. Di alur Bertahap: peninjau.',
            self::Penulis => 'Menulis dan menerbitkan artikelnya sendiri.',
            self::Kontributor => 'Menulis artikel sendiri; diterbitkan oleh Editor.',
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
