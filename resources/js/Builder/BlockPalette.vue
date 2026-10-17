<script setup lang="ts">
import { Plus } from '@lucide/vue';
import draggable from 'vuedraggable';
import { blockIcons } from '@/Builder/blockDefinitions';
import { useBuilderStore } from '@/stores/builder';
import type { AnyPageBlock, BlockCatalogEntry } from '@/types/blocks';

defineProps<{
    catalog: BlockCatalogEntry[];
}>();

const store = useBuilderStore();

// Saat item palet diseret ke kanvas, yang masuk adalah blok baru (bukan entri katalog).
function cloneBlock(entry: BlockCatalogEntry): AnyPageBlock {
    return store.create(entry);
}
</script>

<template>
    <section aria-labelledby="palette-heading" class="grid gap-3">
        <h2 id="palette-heading" class="text-sm font-semibold">Blok</h2>
        <p class="text-xs text-muted-foreground">
            Seret ke kanvas, atau tekan tombol tambah.
        </p>
        <draggable
            :list="catalog"
            item-key="type"
            :group="{ name: 'page-blocks', pull: 'clone', put: false }"
            :clone="cloneBlock"
            :sort="false"
            tag="ul"
            class="flex gap-2 overflow-x-auto pb-1 lg:grid lg:overflow-visible"
        >
            <template #item="{ element }">
                <li
                    class="min-w-56 cursor-grab rounded-md border bg-card p-3 active:cursor-grabbing lg:min-w-0"
                >
                    <div class="flex items-start gap-2">
                        <component
                            :is="
                                blockIcons[
                                    element.type as keyof typeof blockIcons
                                ]
                            "
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">
                                {{ element.label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ element.description }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-2 inline-flex min-h-8 items-center gap-1 rounded-md border px-2 text-xs font-medium hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        @click="store.add(element)"
                    >
                        <Plus class="size-3.5" aria-hidden="true" />
                        Tambah
                        <span class="sr-only">{{ element.label }}</span>
                    </button>
                </li>
            </template>
        </draggable>
    </section>
</template>
