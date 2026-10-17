<?php

namespace App\Services\PageBuilder\Blocks;

use App\Support\SafeUrl;
use Illuminate\Validation\Rule;

/**
 * Banner pembuka halaman dengan judul, subjudul, dan tombol ajakan opsional.
 */
class HeroBannerBlock extends AbstractBlock
{
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
        return 'Judul besar di bagian atas halaman, dengan tombol tautan opsional.';
    }

    public function defaults(): array
    {
        return [
            'heading' => 'Selamat datang di portal resmi dinas',
            'subheading' => null,
            'cta_label' => null,
            'cta_url' => null,
            'variant' => 'primary',
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
        ];
    }

    public function messages(): array
    {
        return ['cta_url.regex' => SafeUrl::MESSAGE];
    }

    public function attributes(): array
    {
        return [
            'heading' => 'judul banner',
            'subheading' => 'subjudul',
            'cta_label' => 'teks tombol',
            'cta_url' => 'tautan tombol',
        ];
    }
}
