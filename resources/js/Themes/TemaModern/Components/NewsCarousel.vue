<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\NewsCarouselBlock), dan logika tombol geser
    ada di sana. Berita Media Center selalu teks polos: tampilkan dengan {{ }}.
-->
<script setup lang="ts">
/**
 * Deret berita yang digeser manual (tombol, sentuhan, atau Tab ke kartu
 * berikutnya). Tidak pernah berputar otomatis. Pola karusel WAI-ARIA:
 * aria-roledescription "carousel" dan "slide" dengan label "n dari m".
 */
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import type { BlockDataMap, NewsCarouselProps } from '@/types/blocks';
import NewsCard from '../Partials/NewsCard.vue';
import SectionHeader from '../Partials/SectionHeader.vue';

const props = defineProps<
    NewsCarouselProps & {
        data: BlockDataMap['NewsCarousel'];
    }
>();

const headingId = useId();
const items = computed(() => props.data?.items ?? []);
const track = ref<HTMLElement | null>(null);
const atStart = ref(true);
const atEnd = ref(false);

/** Lebar kartu per layar; class ditulis utuh agar terdeteksi Tailwind. */
const slideClass = computed(
    () =>
        ({
            2: 'lg:basis-[calc((100%-1.25rem)/2)]',
            3: 'lg:basis-[calc((100%-2.5rem)/3)]',
            4: 'lg:basis-[calc((100%-3.75rem)/4)]',
        })[props.columns] ?? 'lg:basis-[calc((100%-2.5rem)/3)]',
);

const description = computed(() =>
    props.fetch_media_center
        ? 'Kabar terbaru dari dinas dan Media Center Kota Singkawang.'
        : 'Kabar terbaru dari kegiatan dan program dinas.',
);

function updateEdges(): void {
    const element = track.value;

    if (!element) {
        return;
    }

    atStart.value = element.scrollLeft <= 4;
    atEnd.value =
        element.scrollLeft + element.clientWidth >= element.scrollWidth - 4;
}

/** Geser satu kartu; tanpa animasi bila pengguna memilih gerak dikurangi. */
function scrollByCard(direction: 1 | -1): void {
    const element = track.value;
    const card = element?.querySelector<HTMLElement>('li');

    if (!element || !card) {
        return;
    }

    const gap = parseFloat(getComputedStyle(element).columnGap) || 0;
    const reduceMotion =
        window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
        document.documentElement.getAttribute('data-a11y-motion') === 'off';

    element.scrollBy({
        left: direction * (card.offsetWidth + gap),
        behavior: reduceMotion ? 'auto' : 'smooth',
    });
}

let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
    updateEdges();

    if (track.value) {
        resizeObserver = new ResizeObserver(updateEdges);
        resizeObserver.observe(track.value);
    }
});

onBeforeUnmount(() => resizeObserver?.disconnect());
</script>

<template>
    <section class="bg-mist py-14 sm:py-20" :aria-labelledby="headingId">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader
                :title="title ?? 'Berita Pilihan'"
                :heading-id="headingId"
                :description="description"
                action-label="Lihat semua berita"
                :action-href="items.length ? data?.index_url : null"
            />

            <div
                v-if="items.length"
                aria-roledescription="carousel"
                :aria-labelledby="headingId"
            >
                <ul
                    ref="track"
                    class="-mx-4 flex snap-x snap-mandatory scroll-px-4 [scrollbar-width:none] items-start gap-4 overflow-x-auto scroll-smooth px-4 pb-4 sm:-mx-6 sm:scroll-px-6 sm:px-6 lg:mx-0 lg:scroll-px-0 lg:gap-5 lg:px-0 [&::-webkit-scrollbar]:hidden"
                    @scroll.passive="updateEdges"
                >
                    <li
                        v-for="(item, index) in items"
                        :key="item.id"
                        role="group"
                        aria-roledescription="slide"
                        :aria-label="`${index + 1} dari ${items.length}`"
                        class="flex-none basis-[85%] snap-start sm:basis-[calc((100%-1rem)/2)]"
                        :class="slideClass"
                    >
                        <NewsCard :item="item" stacked />
                    </li>
                </ul>

                <div
                    v-if="items.length > 1"
                    class="mt-2 flex items-center justify-end gap-2"
                >
                    <button
                        type="button"
                        class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-grid size-11 place-items-center rounded-full ring-1 transition disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="atStart"
                        @click="scrollByCard(-1)"
                    >
                        <ChevronLeft class="size-5" aria-hidden="true" />
                        <span class="sr-only">Berita sebelumnya</span>
                    </button>
                    <button
                        type="button"
                        class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-grid size-11 place-items-center rounded-full ring-1 transition disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="atEnd"
                        @click="scrollByCard(1)"
                    >
                        <ChevronRight class="size-5" aria-hidden="true" />
                        <span class="sr-only">Berita berikutnya</span>
                    </button>
                </div>
            </div>

            <p
                v-else
                class="rounded-card bg-canvas text-ink-muted ring-line px-6 py-10 text-center ring-1"
            >
                Belum ada berita untuk ditampilkan.
            </p>
        </div>
    </section>
</template>
