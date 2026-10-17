import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import type { AnyPageBlock, BlockCatalogEntry } from '@/types/blocks';

function clone<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

/**
 * State editor Page Builder: daftar blok, blok terpilih, dan status
 * "belum disimpan". Disimpan ke server oleh BuilderEditor.
 */
export const useBuilderStore = defineStore('page-builder', () => {
    const blocks = ref<AnyPageBlock[]>([]);
    const selectedId = ref<string | null>(null);
    const dirty = ref(false);
    const announcement = ref('');

    let loading = false;

    watch(
        blocks,
        () => {
            if (!loading) {
                dirty.value = true;
            }
        },
        { deep: true, flush: 'sync' },
    );

    const selected = computed(
        () =>
            blocks.value.find((block) => block.id === selectedId.value) ?? null,
    );

    const selectedIndex = computed(() =>
        blocks.value.findIndex((block) => block.id === selectedId.value),
    );

    function load(initial: AnyPageBlock[]): void {
        loading = true;
        blocks.value = clone(initial);
        loading = false;
        dirty.value = false;

        if (!blocks.value.some((block) => block.id === selectedId.value)) {
            selectedId.value = blocks.value[0]?.id ?? null;
        }
    }

    function create(entry: BlockCatalogEntry): AnyPageBlock {
        return {
            id: crypto.randomUUID(),
            type: entry.type,
            props: clone(entry.defaults),
        } as AnyPageBlock;
    }

    function add(entry: BlockCatalogEntry, index = blocks.value.length): void {
        const block = create(entry);
        blocks.value.splice(index, 0, block);
        selectedId.value = block.id;
        announcement.value = `Blok ${entry.label} ditambahkan di posisi ${index + 1}.`;
    }

    function remove(id: string): void {
        const index = blocks.value.findIndex((block) => block.id === id);

        if (index === -1) {
            return;
        }

        blocks.value.splice(index, 1);

        if (selectedId.value === id) {
            selectedId.value =
                blocks.value[Math.min(index, blocks.value.length - 1)]?.id ??
                null;
        }

        announcement.value = 'Blok dihapus.';
    }

    function move(id: string, offset: -1 | 1): void {
        const from = blocks.value.findIndex((block) => block.id === id);
        const to = from + offset;

        if (from === -1 || to < 0 || to >= blocks.value.length) {
            return;
        }

        const [block] = blocks.value.splice(from, 1);
        blocks.value.splice(to, 0, block);
        announcement.value = `Blok dipindah ke posisi ${to + 1} dari ${blocks.value.length}.`;
    }

    function duplicate(id: string): void {
        const index = blocks.value.findIndex((block) => block.id === id);

        if (index === -1) {
            return;
        }

        const copy = { ...clone(blocks.value[index]), id: crypto.randomUUID() };
        blocks.value.splice(index + 1, 0, copy);
        selectedId.value = copy.id;
        announcement.value = `Blok diduplikasi ke posisi ${index + 2}.`;
    }

    function select(id: string): void {
        selectedId.value = id;
    }

    function markSaved(): void {
        dirty.value = false;
    }

    return {
        blocks,
        selectedId,
        selected,
        selectedIndex,
        dirty,
        announcement,
        load,
        create,
        add,
        remove,
        move,
        duplicate,
        select,
        markSaved,
    };
});
