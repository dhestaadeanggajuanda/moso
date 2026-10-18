<?php

namespace App\Services\PageBuilder\Blocks;

/**
 * Karusel berita gabungan (lokal + Media Center opsional) yang digeser manual.
 *
 * Sumber data, validasi, dan normalisasi sama persis dengan NewsGridHybrid;
 * `columns` di sini berarti jumlah kartu yang terlihat sekaligus di desktop.
 */
class NewsCarouselBlock extends NewsGridHybridBlock
{
    public function type(): string
    {
        return 'NewsCarousel';
    }

    public function label(): string
    {
        return 'Karusel Berita (Lokal + Media Center)';
    }

    public function description(): string
    {
        return 'Berita terbaru dalam deret yang digeser dengan tombol atau sentuhan, tanpa berputar otomatis.';
    }

    public function defaults(): array
    {
        return [
            ...parent::defaults(),
            'title' => 'Berita Pilihan',
            'limit' => 8,
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'columns' => 'kartu terlihat di desktop',
        ];
    }
}
