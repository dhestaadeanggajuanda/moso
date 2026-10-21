<?php

namespace App\Enums;

/**
 * Jenis tulisan yang diminta ke asisten AI. Keduanya disimpan sebagai Artikel;
 * "kegiatan" hanya mengubah gaya penulisan menjadi liputan kegiatan yang sudah berlangsung.
 */
enum AiDraftKind: string
{
    case Berita = 'berita';
    case Kegiatan = 'kegiatan';

    public function label(): string
    {
        return match ($this) {
            self::Berita => 'Berita atau pengumuman',
            self::Kegiatan => 'Liputan kegiatan',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $kind) => ['value' => $kind->value, 'label' => $kind->label()],
            self::cases(),
        );
    }
}
