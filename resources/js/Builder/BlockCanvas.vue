<script setup lang="ts">
import { ArrowDown, ArrowUp, Copy, GripVertical, Trash2 } from '@lucide/vue';
import draggable from 'vuedraggable';
import { blockIcons, blockSummary } from '@/Builder/blockDefinitions';
import { useBuilderStore } from '@/stores/builder';
import type { BlockCatalogEntry, BlockType } from '@/types/blocks';

const props = defineProps<{
    catalog: BlockCatalogEntry[];
    invalidIds: string[];
}>();

const store = useBuilderStore();

function labelOf(type: BlockType): string {
    return props.catalog.find((entry) => entry.type === type)?.label ?? type;
}

function onAdded(event: { added?: { element: { id: string } } }): void {
    if (event.added) {
        store.select(event.added.element.id);
    }
}
</script>

<template>
    <section aria-labelledby="canvas-heading" class="grid content-start gap-3">
        <h2 id="canvas-heading" class="text-sm font-semibold">
            Susunan halaman
            <span class="font-normal text-muted-foreground"
                >({{ store.blocks.length }} blok)</span
            >
        </h2>

        <draggable
            v-model="store.blocks"
            item-key="id"
            handle=".drag-handle"
            :group="{ name: 'page-blocks' }"
            tag="ol"
            class="grid min-h-40 content-start gap-2 rounded-lg border border-dashed p-2"
            ghost-class="opacity-40"
            @change="onAdded"
        >
            <template #item="{ element, index }">
                <li
                    :class="[
                        'flex items-stretch gap-1 rounded-md border bg-card',
                        element.id === store.selectedId
                            ? 'ring-2 ring-primary'
                            : '',
                        invalidIds.includes(element.id)
                            ? 'border-destructive'
                            : '',
                    ]"
                >
                    <span
                        class="drag-handle flex w-8 shrink-0 cursor-grab items-center justify-center text-muted-foreground active:cursor-grabbing"
                        title="Seret untuk memindah"
                        aria-hidden="true"
                    >
                        <GripVertical class="size-4" />
                    </span>

                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-start gap-2 py-3 text-left focus-visible:underline focus-visible:outline-none"
                        :aria-pressed="element.id === store.selectedId"
                        @click="store.select(element.id)"
                    >
                        <component
                            :is="blockIcons[element.type as BlockType]"
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium"
                                >{{ index + 1 }}.
                                {{ labelOf(element.type) }}</span
                            >
                            <span
                                class="block truncate text-xs text-muted-foreground"
                                >{{ blockSummary(element) }}</span
                            >
                            <span
                                v-if="invalidIds.includes(element.id)"
                                class="block text-xs text-destructive"
                                >Ada isian yang perlu diperbaiki</span
                            >
                        </span>
                    </button>

                    <div class="flex shrink-0 items-center gap-0.5 pr-1">
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md hover:bg-accent disabled:opacity-30"
                            :disabled="index === 0"
                            :aria-label="`Naikkan blok ${index + 1}`"
                            @click="store.move(element.id, -1)"
                        >
                            <ArrowUp class="size-4" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md hover:bg-accent disabled:opacity-30"
                            :disabled="index === store.blocks.length - 1"
                            :aria-label="`Turunkan blok ${index + 1}`"
                            @click="store.move(element.id, 1)"
                        >
                            <ArrowDown class="size-4" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md hover:bg-accent"
                            :aria-label="`Duplikat blok ${index + 1}`"
                            @click="store.duplicate(element.id)"
                        >
                            <Copy class="size-4" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md text-destructive hover:bg-accent"
                            :aria-label="`Hapus blok ${index + 1}`"
                            @click="store.remove(element.id)"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </li>
            </template>

            <template #footer>
                <li
                    v-if="store.blocks.length === 0"
                    class="p-6 text-center text-sm text-muted-foreground"
                >
                    Halaman masih kosong. Seret blok ke sini atau tekan
                    "Tambah".
                </li>
            </template>
        </draggable>
    </section>
</template>
