<?php

namespace App\Enums;

/**
 * Jenis dokumen Sistem Akuntabilitas Kinerja Instansi Pemerintah (SAKIP).
 */
enum SakipDocumentType: string
{
    case Renstra = 'renstra';
    case Renja = 'renja';
    case Iku = 'iku';
    case PerjanjianKinerja = 'perjanjian_kinerja';
    case RencanaAksi = 'rencana_aksi';
    case Lkjip = 'lkjip';

    public function label(): string
    {
        return match ($this) {
            self::Renstra => 'Rencana Strategis (Renstra)',
            self::Renja => 'Rencana Kerja (Renja)',
            self::Iku => 'Indikator Kinerja Utama (IKU)',
            self::PerjanjianKinerja => 'Perjanjian Kinerja (PK)',
            self::RencanaAksi => 'Rencana Aksi',
            self::Lkjip => 'Laporan Kinerja (LKjIP)',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
