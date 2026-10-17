<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Agenda;

/**
 * Daftar agenda kegiatan terdekat yang sudah terbit.
 */
class AgendaKegiatanBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'AgendaKegiatan';
    }

    public function label(): string
    {
        return 'Agenda Kegiatan';
    }

    public function description(): string
    {
        return 'Kegiatan terdekat yang dijadwalkan dinas.';
    }

    public function defaults(): array
    {
        return [
            'title' => 'Agenda Kegiatan',
            'limit' => 4,
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'limit' => ['required', 'integer', 'between:1,12'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'judul blok', 'limit' => 'jumlah agenda'];
    }

    public function normalize(array $props): array
    {
        $props = parent::normalize($props);

        return [
            'title' => filled($props['title']) ? (string) $props['title'] : null,
            'limit' => (int) $props['limit'],
        ];
    }

    public function hydrate(array $props): ?array
    {
        return [
            'items' => Agenda::query()
                ->upcoming()
                ->limit($this->normalize($props)['limit'])
                ->get()
                ->map(fn (Agenda $agenda) => [
                    'id' => $agenda->id,
                    'title' => $agenda->title,
                    'location' => $agenda->location,
                    'organizer' => $agenda->organizer,
                    'start_at' => $agenda->start_at->toIso8601String(),
                    'end_at' => $agenda->end_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
