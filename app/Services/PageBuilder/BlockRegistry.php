<?php

namespace App\Services\PageBuilder;

use App\Services\PageBuilder\Blocks\AgendaKegiatanBlock;
use App\Services\PageBuilder\Blocks\HeroBannerBlock;
use App\Services\PageBuilder\Blocks\NewsGridHybridBlock;
use App\Services\PageBuilder\Blocks\RichTextBlock;
use Illuminate\Contracts\Container\Container;

/**
 * Whitelist tipe blok Page Builder (OWASP A08). Blok yang tidak terdaftar di
 * sini ditolak saat disimpan dan dilewati saat dirender.
 */
class BlockRegistry
{
    /**
     * @var list<class-string<BlockDefinition>>
     */
    private const BLOCKS = [
        HeroBannerBlock::class,
        NewsGridHybridBlock::class,
        AgendaKegiatanBlock::class,
        RichTextBlock::class,
    ];

    /**
     * @var array<string, BlockDefinition>|null
     */
    private ?array $definitions = null;

    public function __construct(private Container $container) {}

    /**
     * @return array<string, BlockDefinition>
     */
    public function all(): array
    {
        if ($this->definitions === null) {
            $this->definitions = [];

            foreach (self::BLOCKS as $class) {
                $definition = $this->container->make($class);
                $this->definitions[$definition->type()] = $definition;
            }
        }

        return $this->definitions;
    }

    public function get(string $type): ?BlockDefinition
    {
        return $this->all()[$type] ?? null;
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->all());
    }

    /**
     * Katalog blok untuk palet Page Builder.
     *
     * @return list<array{type: string, label: string, description: string, defaults: array<string, mixed>}>
     */
    public function catalog(): array
    {
        return array_values(array_map(fn (BlockDefinition $definition) => [
            'type' => $definition->type(),
            'label' => $definition->label(),
            'description' => $definition->description(),
            'defaults' => $definition->defaults(),
        ], $this->all()));
    }

    /**
     * Bentuk akhir blok yang disimpan: hanya id, type, dan props yang dikenal.
     *
     * @param  array<int, array{id: string, type: string, props?: array<string, mixed>}>  $blocks
     * @return list<array{id: string, type: string, props: array<string, mixed>}>
     */
    public function normalizeBlocks(array $blocks): array
    {
        $normalized = [];

        foreach ($blocks as $block) {
            $definition = $this->get($block['type']);

            if ($definition !== null) {
                $normalized[] = [
                    'id' => $block['id'],
                    'type' => $definition->type(),
                    'props' => $definition->normalize($block['props'] ?? []),
                ];
            }
        }

        return $normalized;
    }
}
