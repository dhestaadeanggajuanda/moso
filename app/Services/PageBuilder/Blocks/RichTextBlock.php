<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\Content\HtmlSanitizer;

/**
 * Konten teks bebas. HTML disanitasi di server sebelum disimpan, sehingga
 * aman dirender tema dengan v-html.
 */
class RichTextBlock extends AbstractBlock
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function type(): string
    {
        return 'RichText';
    }

    public function label(): string
    {
        return 'Teks';
    }

    public function description(): string
    {
        return 'Paragraf, judul, daftar, dan tautan. Mendukung HTML dasar.';
    }

    public function defaults(): array
    {
        return ['html' => ''];
    }

    public function rules(): array
    {
        return ['html' => ['nullable', 'string', 'max:50000']];
    }

    public function attributes(): array
    {
        return ['html' => 'isi teks'];
    }

    public function normalize(array $props): array
    {
        return ['html' => $this->sanitizer->sanitize((string) (parent::normalize($props)['html'] ?? ''))];
    }
}
