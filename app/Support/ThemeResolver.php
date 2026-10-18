<?php

namespace App\Support;

/**
 * Menentukan tema untuk satu halaman publik.
 *
 * Urutan: tema halaman → tema aktif di pengaturan (theme.active) → tema
 * bawaan. Hanya tema yang dikompilasi (config themes.enabled) yang diterima,
 * sehingga nilai dari database tidak pernah bisa menunjuk path sembarang.
 */
class ThemeResolver
{
    public function __construct(private Settings $settings) {}

    /**
     * @return list<string>
     */
    public function enabled(): array
    {
        /** @var list<string> */
        return config('themes.enabled');
    }

    public function resolve(?string $preferred = null): string
    {
        foreach ([$preferred, $this->settings->get('theme.active')] as $candidate) {
            if ($candidate !== null && in_array($candidate, $this->enabled(), true)) {
                return $candidate;
            }
        }

        return (string) config('themes.default');
    }
}
