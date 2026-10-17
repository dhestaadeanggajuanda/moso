<?php

namespace App\Enums;

/**
 * Klasifikasi informasi publik menurut UU 14/2008 tentang Keterbukaan Informasi Publik.
 */
enum PublicDocumentCategory: string
{
    case Berkala = 'berkala';
    case SertaMerta = 'serta_merta';
    case SetiapSaat = 'setiap_saat';

    public function label(): string
    {
        return match ($this) {
            self::Berkala => 'Informasi Berkala',
            self::SertaMerta => 'Informasi Serta Merta',
            self::SetiapSaat => 'Informasi Setiap Saat',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
