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
     * Penjelasan singkat untuk warga, mengacu pada Pasal 9 sampai 11 UU 14/2008.
     */
    public function description(): string
    {
        return match ($this) {
            self::Berkala => 'Diumumkan rutin sekurang-kurangnya enam bulan sekali, misalnya profil, ringkasan kinerja, dan laporan keuangan.',
            self::SertaMerta => 'Menyangkut hajat hidup orang banyak dan ketertiban umum, sehingga diumumkan tanpa penundaan.',
            self::SetiapSaat => 'Tersedia dan dapat diminta kapan saja, misalnya daftar informasi publik, kebijakan, dan perjanjian.',
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
