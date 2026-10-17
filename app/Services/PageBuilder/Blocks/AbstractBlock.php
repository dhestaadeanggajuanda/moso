<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\BlockDefinition;

/**
 * Perilaku bawaan: props di luar defaults() dibuang, props yang kosong diisi
 * nilai bawaan, dan blok tidak membutuhkan data dinamis.
 */
abstract class AbstractBlock implements BlockDefinition
{
    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    public function normalize(array $props): array
    {
        return array_intersect_key($props, $this->defaults()) + $this->defaults();
    }

    public function hydrate(array $props): ?array
    {
        return null;
    }
}
