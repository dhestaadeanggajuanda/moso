<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\NewsGridHybridBlock).
    Berita Media Center selalu teks polos: tampilkan dengan {{ }}, JANGAN v-html.
-->
<script setup lang="ts">
import { computed, useId } from 'vue';
import type { BlockDataMap, NewsGridHybridProps } from '@/types/blocks';
import NewsCard from '../Partials/NewsCard.vue';
import SectionHeader from '../Partials/SectionHeader.vue';

const props = defineProps<
    NewsGridHybridProps & {
        data: BlockDataMap['NewsGridHybrid'];
    }
>();

const headingId = useId();
const items = computed(() => props.data?.items ?? []);

/** Berita pertama tampil besar bila ada cukup berita untuk mengisi grid. */
const featured = computed(() =>
    items.value.length >= 3 ? items.value[0] : null,
);
const rest = computed(() =>
    featured.value ? items.value.slice(1) : items.value,
);

/** Class ditulis utuh agar terdeteksi Tailwind. */
const columnClass = computed(
    () =>
        ({
            2: 'lg:grid-cols-2',
            3: 'lg:grid-cols-3',
            4: 'lg:grid-cols-4',
        })[props.columns] ?? 'lg:grid-cols-3',
);

const description = computed(() =>
    props.fetch_media_center
        ? 'Kabar terbaru dari dinas dan Media Center Kota Singkawang.'
        : 'Kabar terbaru dari kegiatan dan program dinas.',
);
</script>

<template>
    <section class="bg-mist py-14 sm:py-20" :aria-labelledby="headingId">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader
                :title="title ?? 'Berita Terkini'"
                :heading-id="headingId"
                :description="description"
                action-label="Lihat semua berita"
                :action-href="items.length ? data?.index_url : null"
            />

            <template v-if="items.length">
                <NewsCard
                    v-if="featured"
                    :item="featured"
                    featured
                    class="mb-5"
                />

                <ul
                    v-if="rest.length"
                    class="grid gap-5 sm:grid-cols-2"
                    :class="columnClass"
                >
                    <li v-for="item in rest" :key="item.id">
                        <NewsCard :item="item" />
                    </li>
                </ul>
            </template>

            <p
                v-else
                class="rounded-card bg-canvas text-ink-muted ring-line px-6 py-10 text-center ring-1"
            >
                Belum ada berita untuk ditampilkan.
            </p>
        </div>
    </section>
</template>
