<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Service;

/**
 * Daftar layanan publik yang terbit, dengan kolom pencarian ke halaman /layanan.
 */
class LayananListBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'LayananList';
    }

    public function label(): string
    {
        return 'Daftar Layanan';
    }

    public function description(): string
    {
        return 'Layanan publik dinas beserta jangka waktu dan biayanya, dengan kolom cari layanan.';
    }

    public function defaults(): array
    {
        return [
            'title' => 'Layanan Publik',
            'limit' => 6,
            'show_search' => true,
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'limit' => ['required', 'integer', 'between:1,24'],
            'show_search' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul blok',
            'limit' => 'jumlah layanan',
            'show_search' => 'kolom cari layanan',
        ];
    }

    public function normalize(array $props): array
    {
        $props = parent::normalize($props);

        return [
            'title' => filled($props['title']) ? (string) $props['title'] : null,
            'limit' => (int) $props['limit'],
            'show_search' => filter_var($props['show_search'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, index_url: string}
     */
    public function hydrate(array $props): ?array
    {
        return [
            'items' => array_values(Service::query()
                ->published()
                ->limit($this->normalize($props)['limit'])
                ->get()
                ->map(fn (Service $service) => $service->toPublicSummary())
                ->all()),
            'total' => Service::query()->where('is_published', true)->count(),
            'index_url' => route('site.services.index'),
        ];
    }
}
