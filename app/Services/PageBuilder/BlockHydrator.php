<?php

namespace App\Services\PageBuilder;

use Throwable;

/**
 * Menyuntikkan data dinamis ke setiap blok sebelum halaman dirender.
 *
 * Kegagalan satu blok (mis. database atau layanan luar bermasalah) tidak
 * menggagalkan halaman: blok itu menerima data null dan errornya dilaporkan (A10).
 */
class BlockHydrator
{
    public function __construct(private BlockRegistry $registry) {}

    /**
     * @param  array<int, array{id: string, type: string, props: array<string, mixed>}>  $blocks
     * @return list<array{id: string, type: string, props: array<string, mixed>, data: array<string, mixed>|null}>
     */
    public function hydrate(array $blocks): array
    {
        $hydrated = [];

        foreach ($blocks as $block) {
            $definition = $this->registry->get($block['type']);

            if ($definition === null) {
                continue;
            }

            try {
                $data = $definition->hydrate($block['props']);
            } catch (Throwable $e) {
                report($e);
                $data = null;
            }

            $hydrated[] = [
                'id' => $block['id'],
                'type' => $block['type'],
                'props' => $definition->normalize($block['props']),
                'data' => $data,
            ];
        }

        return $hydrated;
    }
}
