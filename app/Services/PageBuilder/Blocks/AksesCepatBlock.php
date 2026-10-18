<?php

namespace App\Services\PageBuilder\Blocks;

use App\Support\SafeUrl;
use Illuminate\Validation\Rule;

/**
 * Deret tautan cepat berikon (mis. Layanan, Pengaduan, PPID, E-SAKIP). Isi dan
 * tujuan tautan diatur pengelola, sehingga tema tetap netral terhadap dinas.
 */
class AksesCepatBlock extends AbstractBlock
{
    public const MAX_ITEMS = 8;

    /**
     * Ikon yang boleh dipilih; tema memetakan nama ini ke ikon Lucide.
     */
    public const ICONS = [
        'layanan', 'pengaduan', 'dokumen', 'laporan', 'kontak', 'berita', 'agenda', 'lokasi',
    ];

    public function type(): string
    {
        return 'AksesCepat';
    }

    public function label(): string
    {
        return 'Akses Cepat';
    }

    public function description(): string
    {
        return 'Deret tautan berikon ke layanan, pengaduan, PPID, E-SAKIP, atau halaman penting lain.';
    }

    /**
     * Pencarian layanan sudah ada di banner, jadi tautan bawaan mengarah ke
     * halaman kepatuhan (PPID, E-SAKIP) dan kanal pengaduan. Tiga tautan
     * supaya label tetap muat satu baris di ponsel 360px.
     */
    public function defaults(): array
    {
        return [
            'title' => null,
            'items' => [
                ['label' => 'PPID', 'url' => '/ppid', 'icon' => 'dokumen'],
                ['label' => 'E-SAKIP', 'url' => '/sakip', 'icon' => 'laporan'],
                ['label' => 'Pengaduan', 'url' => 'https://www.lapor.go.id', 'icon' => 'pengaduan'],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.label' => ['required', 'string', 'max:40'],
            'items.*.url' => ['required', 'string', 'max:500', 'regex:'.SafeUrl::PATTERN],
            'items.*.icon' => ['required', Rule::in(self::ICONS)],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.url.regex' => SafeUrl::MESSAGE,
            'items.min' => 'Isi minimal satu tautan.',
            'items.max' => 'Maksimal '.self::MAX_ITEMS.' tautan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul blok',
            'items.*.label' => 'teks tautan',
            'items.*.url' => 'alamat tautan',
            'items.*.icon' => 'ikon',
        ];
    }

    /**
     * Hanya label, url, dan ikon yang disimpan untuk setiap tautan.
     */
    public function normalize(array $props): array
    {
        $props = parent::normalize($props);
        $items = is_array($props['items']) ? $props['items'] : [];

        return [
            'title' => filled($props['title']) ? (string) $props['title'] : null,
            'items' => array_values(array_map(fn (mixed $item): array => [
                'label' => (string) (is_array($item) ? ($item['label'] ?? '') : ''),
                'url' => (string) (is_array($item) ? ($item['url'] ?? '') : ''),
                'icon' => (string) (is_array($item) ? ($item['icon'] ?? 'layanan') : 'layanan'),
            ], array_slice($items, 0, self::MAX_ITEMS))),
        ];
    }
}
