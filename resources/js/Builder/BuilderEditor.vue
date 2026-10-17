<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import PageBuilderController from '@/actions/App/Http/Controllers/Admin/PageBuilderController';
import BlockCanvas from '@/Builder/BlockCanvas.vue';
import BlockPalette from '@/Builder/BlockPalette.vue';
import AgendaKegiatanInspector from '@/Builder/inspectors/AgendaKegiatanInspector.vue';
import HeroBannerInspector from '@/Builder/inspectors/HeroBannerInspector.vue';
import NewsGridHybridInspector from '@/Builder/inspectors/NewsGridHybridInspector.vue';
import RichTextInspector from '@/Builder/inspectors/RichTextInspector.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useBuilderStore } from '@/stores/builder';
import type { Option } from '@/types/admin';
import type {
    AnyPageBlock,
    BlockCatalogEntry,
    BlockType,
} from '@/types/blocks';

const props = defineProps<{
    pageId: number;
    version: string | null;
    blocks: AnyPageBlock[];
    catalog: BlockCatalogEntry[];
    categories: Option[];
    mediaCenterModes: Option[];
}>();

const store = useBuilderStore();
const page = usePage();
const saving = ref(false);

const inspectors: Record<BlockType, Component> = {
    HeroBanner: HeroBannerInspector,
    NewsGridHybrid: NewsGridHybridInspector,
    AgendaKegiatan: AgendaKegiatanInspector,
    RichText: RichTextInspector,
};

store.load(props.blocks);

// Setelah simpan, server mengembalikan versi ternormalisasi (mis. HTML tersanitasi).
watch(
    () => props.version,
    () => store.load(props.blocks),
);

const errors = computed(() => page.props.errors as Record<string, string>);

/** Pesan error props blok terpilih, dengan key nama prop. */
const selectedErrors = computed(() => {
    const prefix = `blocks.${store.selectedIndex}.props.`;

    return Object.fromEntries(
        Object.entries(errors.value)
            .filter(([key]) => key.startsWith(prefix))
            .map(([key, message]) => [key.slice(prefix.length), message]),
    );
});

const invalidIds = computed(() =>
    store.blocks
        .filter((_, index) =>
            Object.keys(errors.value).some((key) =>
                key.startsWith(`blocks.${index}.`),
            ),
        )
        .map((block) => block.id),
);

const generalError = computed(
    () =>
        errors.value.blocks ??
        Object.entries(errors.value).find(([key]) =>
            /^blocks\.\d+\.(id|type)$/.test(key),
        )?.[1],
);

function save(): void {
    router.put(
        PageBuilderController.update.url(props.pageId),
        { blocks: store.blocks },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
            onSuccess: () => store.markSaved(),
        },
    );
}

function warnBeforeUnload(event: BeforeUnloadEvent): void {
    if (store.dirty) {
        event.preventDefault();
    }
}

let removeNavigationGuard: (() => void) | undefined;

onMounted(() => {
    window.addEventListener('beforeunload', warnBeforeUnload);

    removeNavigationGuard = router.on('before', (event) => {
        const visit = event.detail.visit;

        if (
            store.dirty &&
            visit.method === 'get' &&
            !window.confirm('Perubahan belum disimpan. Tinggalkan halaman ini?')
        ) {
            event.preventDefault();
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', warnBeforeUnload);
    removeNavigationGuard?.();
});
</script>

<template>
    <div class="grid gap-4">
        <div
            class="sticky top-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-b bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6"
        >
            <div class="flex items-center gap-2 text-sm">
                <Badge v-if="store.dirty" variant="secondary"
                    >Belum disimpan</Badge
                >
                <span v-else class="text-muted-foreground">Tersimpan</span>
            </div>
            <Button :disabled="saving || !store.dirty" @click="save">
                {{ saving ? 'Menyimpan…' : 'Simpan susunan' }}
            </Button>
        </div>

        <p
            v-if="generalError"
            role="alert"
            class="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive"
        >
            {{ generalError }}
        </p>

        <!-- Pengumuman untuk pembaca layar saat blok ditambah/dipindah/dihapus. -->
        <p class="sr-only" aria-live="polite">{{ store.announcement }}</p>

        <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)_20rem]">
            <BlockPalette :catalog="catalog" />

            <BlockCanvas :catalog="catalog" :invalid-ids="invalidIds" />

            <section
                aria-labelledby="inspector-heading"
                class="grid content-start gap-3 lg:sticky lg:top-20"
            >
                <h2 id="inspector-heading" class="text-sm font-semibold">
                    Pengaturan blok
                </h2>
                <p
                    v-if="!store.selected"
                    class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Pilih blok di kanvas untuk mengubah isinya.
                </p>
                <component
                    :is="inspectors[store.selected.type]"
                    v-else
                    :key="store.selected.id"
                    :errors="selectedErrors"
                    :categories="categories"
                    :modes="mediaCenterModes"
                />
            </section>
        </div>
    </div>
</template>
