<?php

namespace App\Enums;

/**
 * Slot logo di header situs publik. Path berkasnya disimpan di pengaturan
 * `site.logo_*` dan dibaca App\Support\SiteContext.
 */
enum SiteLogo: string
{
    case Pemkot = 'pemkot';
    case Dinas = 'dinas';

    /**
     * Folder unggahan logo di disk s3.
     */
    public const DIRECTORY = 'branding';

    public function settingKey(): string
    {
        return 'site.logo_'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pemkot => 'Logo Pemerintah Kota',
            self::Dinas => 'Logo dinas',
        };
    }

    /**
     * @return list<string>
     */
    public static function settingKeys(): array
    {
        return array_map(fn (self $logo) => $logo->settingKey(), self::cases());
    }
}
