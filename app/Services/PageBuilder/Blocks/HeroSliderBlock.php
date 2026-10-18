<?php

namespace App\Services\PageBuilder\Blocks;

use App\Support\SafeUrl;
use Illuminate\Support\Facades\Storage;

/**
 * Banner bergeser berisi beberapa slide (foto, judul, tombol opsional).
 *
 * Tidak berputar otomatis kecuali `autoplay` diaktifkan; tema wajib
 * menyediakan tombol jeda dan menghormati prefers-reduced-motion (WCAG 2.2.2).
 * Foto memakai unggahan yang sama dengan HeroBanner (folder page-builder).
 */
class HeroSliderBlock extends AbstractBlock
{
    public const MAX_SLIDES = 5;

    public function type(): string
    {
        return 'HeroSlider';
    }

    public function label(): string
    {
        return 'Banner Bergeser';
    }

    public function description(): string
    {
        return 'Dua sampai lima slide berfoto dengan judul dan tombol; bergeser manual, opsional otomatis.';
    }

    public function defaults(): array
    {
        return [
            'slides' => [
                $this->emptySlide('Selamat datang di portal resmi dinas'),
                $this->emptySlide('Layanan publik yang mudah dijangkau'),
            ],
            'autoplay' => false,
            'interval' => 7,
            'show_search' => false,
        ];
    }

    public function rules(): array
    {
        return [
            'slides' => ['required', 'array', 'min:1', 'max:'.self::MAX_SLIDES],
            'slides.*.heading' => ['required', 'string', 'max:120'],
            'slides.*.subheading' => ['nullable', 'string', 'max:300'],
            'slides.*.cta_label' => ['nullable', 'string', 'max:40', 'required_with:slides.*.cta_url'],
            'slides.*.cta_url' => ['nullable', 'string', 'max:500', 'required_with:slides.*.cta_label', 'regex:'.SafeUrl::PATTERN],
            'slides.*.image_path' => ['nullable', 'string', 'regex:'.HeroBannerBlock::IMAGE_PATH_PATTERN],
            'slides.*.image_alt' => ['nullable', 'string', 'max:160'],
            'autoplay' => ['required', 'boolean'],
            'interval' => ['required', 'integer', 'between:5,15'],
            'show_search' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slides.min' => 'Isi minimal satu slide.',
            'slides.max' => 'Maksimal '.self::MAX_SLIDES.' slide.',
            'slides.*.cta_url.regex' => SafeUrl::MESSAGE,
            'slides.*.image_path.regex' => 'Foto slide tidak valid. Unggah ulang fotonya.',
        ];
    }

    public function attributes(): array
    {
        return [
            'slides.*.heading' => 'judul slide',
            'slides.*.subheading' => 'subjudul slide',
            'slides.*.cta_label' => 'teks tombol',
            'slides.*.cta_url' => 'tautan tombol',
            'slides.*.image_alt' => 'teks alternatif foto',
            'interval' => 'jeda antarslide',
        ];
    }

    /**
     * Hanya kolom slide yang dikenal yang disimpan, maksimal lima slide.
     */
    public function normalize(array $props): array
    {
        $props = parent::normalize($props);
        $slides = is_array($props['slides']) ? array_slice($props['slides'], 0, self::MAX_SLIDES) : [];

        return [
            'slides' => array_values(array_map(fn (mixed $slide): array => [
                'heading' => $this->text($slide, 'heading') ?? '',
                'subheading' => $this->text($slide, 'subheading'),
                'cta_label' => $this->text($slide, 'cta_label'),
                'cta_url' => $this->text($slide, 'cta_url'),
                'image_path' => $this->text($slide, 'image_path'),
                'image_alt' => $this->text($slide, 'image_alt'),
            ], $slides)),
            'autoplay' => filter_var($props['autoplay'], FILTER_VALIDATE_BOOLEAN),
            'interval' => (int) $props['interval'],
            'show_search' => filter_var($props['show_search'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * URL foto per slide (urutan sama dengan props.slides) dan tautan cari layanan.
     *
     * @return array{images: list<string|null>, search_url: string}
     */
    public function hydrate(array $props): ?array
    {
        return [
            'images' => array_values(array_map(
                fn (array $slide): ?string => is_string($slide['image_path'])
                    && preg_match(HeroBannerBlock::IMAGE_PATH_PATTERN, $slide['image_path']) === 1
                        ? Storage::disk('s3')->url($slide['image_path'])
                        : null,
                $this->normalize($props)['slides'],
            )),
            'search_url' => route('site.services.index'),
        ];
    }

    /**
     * @return array{heading: string, subheading: null, cta_label: null, cta_url: null, image_path: null, image_alt: null}
     */
    private function emptySlide(string $heading): array
    {
        return [
            'heading' => $heading,
            'subheading' => null,
            'cta_label' => null,
            'cta_url' => null,
            'image_path' => null,
            'image_alt' => null,
        ];
    }

    private function text(mixed $slide, string $key): ?string
    {
        $value = is_array($slide) ? ($slide[$key] ?? null) : null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
