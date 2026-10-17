<?php

namespace App\Enums;

/**
 * Posisi menu di tema publik.
 */
enum MenuLocation: string
{
    case Header = 'header';
    case Footer = 'footer';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Menu utama (header)',
            self::Footer => 'Menu footer',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $location) => ['value' => $location->value, 'label' => $location->label()],
            self::cases(),
        );
    }
}
