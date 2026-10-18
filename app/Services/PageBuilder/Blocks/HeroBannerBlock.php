<?php

namespace App\Services\PageBuilder\Blocks;

use App\Support\SafeUrl;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Banner pembuka halaman dengan judul, subjudul, foto latar, dan tombol ajakan opsional.
 */
class HeroBannerBlock extends AbstractBlock
{
    /**
     * Folder unggahan foto banner (SecureUploadService).
     */
    public const IMAGE_DIRECTORY = 'page-builder';

    /**
     * Hanya path hasil SecureUploadService yang diterima, bukan path sembarang di bucket.
     */
    public const IMAGE_PATH_PATTERN = '/^page-builder\/\d{4}\/\d{2}\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.webp$/';

    public function type(): string
    {
        return 'HeroBanner';
    }

    public function label(): string
    {
        return 'Banner Utama';
    }

    public function description(): string
    {
        return 'Judul besar di bagian atas halaman, dengan foto latar, kolom cari layanan, dan tombol tautan opsional.';
    }

    public function defaults(): array
    {
        return [
            'heading' => 'Selamat datang di portal resmi dinas',
            'subheading' => null,
            'cta_label' => null,
            'cta_url' => null,
            'variant' => 'primary',
            'image_path' => null,
            'image_alt' => null,
            'show_search' => true,
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:120'],
            'subheading' => ['nullable', 'string', 'max:300'],
            'cta_label' => ['nullable', 'string', 'max:40', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'string', 'max:500', 'required_with:cta_label', 'regex:'.SafeUrl::PATTERN],
            'variant' => ['required', Rule::in(['primary', 'muted'])],
            'image_path' => ['nullable', 'string', 'regex:'.self::IMAGE_PATH_PATTERN],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'show_search' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cta_url.regex' => SafeUrl::MESSAGE,
            'image_path.regex' => 'Foto banner tidak valid. Unggah ulang fotonya.',
        ];
    }

    public function attributes(): array
    {
        return [
            'heading' => 'judul banner',
            'subheading' => 'subjudul',
            'cta_label' => 'teks tombol',
            'cta_url' => 'tautan tombol',
            'image_path' => 'foto banner',
            'image_alt' => 'teks alternatif foto',
            'show_search' => 'kolom cari layanan',
        ];
    }

    public function normalize(array $props): array
    {
        $props = parent::normalize($props);
        $props['show_search'] = filter_var($props['show_search'], FILTER_VALIDATE_BOOLEAN);

        return $props;
    }

    /**
     * @return array{image_url: string|null, search_url: string}
     */
    public function hydrate(array $props): ?array
    {
        $path = $this->normalize($props)['image_path'];

        return [
            'image_url' => is_string($path) && preg_match(self::IMAGE_PATH_PATTERN, $path) === 1
                ? Storage::disk('s3')->url($path)
                : null,
            'search_url' => route('site.services.index'),
        ];
    }
}
