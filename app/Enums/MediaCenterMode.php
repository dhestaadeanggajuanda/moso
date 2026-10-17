<?php

namespace App\Enums;

/**
 * Cara mencari berita di Media Center berdasarkan kata kunci.
 */
enum MediaCenterMode: string
{
    case Tag = 'tag';
    case Category = 'category';
    case Search = 'search';

    public function label(): string
    {
        return match ($this) {
            self::Tag => 'Tag (slug)',
            self::Category => 'Kategori (slug)',
            self::Search => 'Pencarian teks',
        };
    }

    /**
     * Endpoint taksonomi WordPress untuk mode berbasis slug.
     */
    public function taxonomy(): ?string
    {
        return match ($this) {
            self::Tag => 'tags',
            self::Category => 'categories',
            self::Search => null,
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode) => ['value' => $mode->value, 'label' => $mode->label()],
            self::cases(),
        );
    }
}
