<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\HeroSliderBlock), dan aturan aksesibilitas
    karusel (tombol jeda, berhenti saat difokus/hover, prefers-reduced-motion)
    ada di sana.
-->
<script setup lang="ts">
/**
 * Banner bergeser (pola karusel WAI-ARIA). Bergeser otomatis hanya bila
 * `autoplay` aktif, berhenti saat kursor atau fokus keyboard berada di dalam
 * karusel, tidak pernah berjalan bila pengguna memilih gerak dikurangi, dan
 * selalu bisa dijeda (WCAG 2.2.2). Slide yang tidak aktif dibuat inert.
 */
import {
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    Pause,
    Play,
    Search,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';
import type { BlockDataMap, HeroSliderProps } from '@/types/blocks';

const props = defineProps<
    HeroSliderProps & {
        data: BlockDataMap['HeroSlider'];
    }
>();

const carouselId = useId();
const searchId = useId();
const active = ref(0);
const pausedByUser = ref(false);
const interacting = ref(false);
const reducedMotion = ref(true);

const count = computed(() => props.slides.length);
const canAutoplay = computed(
    () => props.autoplay && count.value > 1 && !reducedMotion.value,
);
const playing = computed(
    () => canAutoplay.value && !pausedByUser.value && !interacting.value,
);

function go(index: number): void {
    active.value = (index + count.value) % count.value;
}

function onControlsKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowRight') {
        event.preventDefault();
        go(active.value + 1);
    } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        go(active.value - 1);
    }
}

function onFocusOut(event: FocusEvent): void {
    const root = event.currentTarget as HTMLElement;

    if (!root.contains(event.relatedTarget as Node | null)) {
        interacting.value = false;
    }
}

let timer: ReturnType<typeof setInterval> | null = null;

function stopTimer(): void {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

function restartTimer(): void {
    stopTimer();

    if (playing.value) {
        timer = setInterval(
            () => go(active.value + 1),
            Math.max(5, props.interval) * 1000,
        );
    }
}

watch([playing, active], restartTimer);

onMounted(() => {
    const query = window.matchMedia('(prefers-reduced-motion: reduce)');
    reducedMotion.value = query.matches;
    query.addEventListener('change', (event) => {
        reducedMotion.value = event.matches;
    });
    restartTimer();
});

onBeforeUnmount(stopTimer);
</script>

<template>
    <section
        :id="carouselId"
        class="bg-brand-900 relative isolate overflow-hidden text-white"
        aria-roledescription="carousel"
        aria-label="Sorotan"
        @mouseenter="interacting = true"
        @mouseleave="interacting = false"
        @focusin="interacting = true"
        @focusout="onFocusOut"
    >
        <!-- Lapisan foto: berganti dengan memudar; tanpa foto tetap bidang navy. -->
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <template v-for="(image, index) in data?.images ?? []" :key="index">
                <img
                    v-if="image"
                    :src="image"
                    alt=""
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'auto'"
                    decoding="async"
                    class="ease-out-expo absolute inset-0 size-full object-cover transition-opacity duration-700"
                    :class="index === active ? 'opacity-100' : 'opacity-0'"
                />
            </template>
            <div
                class="from-brand-950/95 via-brand-950/75 to-brand-950/35 lg:from-brand-950/92 lg:via-brand-950/70 lg:to-brand-950/10 absolute inset-0 bg-linear-to-t lg:bg-linear-to-r"
            />
        </div>

        <div
            class="mx-auto flex min-h-[28rem] max-w-7xl flex-col justify-end px-4 pt-16 pb-8 sm:min-h-[32rem] sm:px-6 lg:min-h-[36rem] lg:justify-center lg:px-8 lg:py-20"
        >
            <!-- Semua slide di sel grid yang sama: tinggi mengikuti slide terpanjang, tanpa lompatan. -->
            <div
                class="grid max-w-3xl [grid-template-areas:'slide']"
                :aria-live="playing ? 'off' : 'polite'"
            >
                <div
                    v-for="(slide, index) in slides"
                    :key="index"
                    role="group"
                    aria-roledescription="slide"
                    :aria-label="`${index + 1} dari ${count}`"
                    :inert="index !== active"
                    class="ease-out-expo transition-opacity duration-500 [grid-area:slide]"
                    :class="
                        index === active ? 'opacity-100' : 'invisible opacity-0'
                    "
                >
                    <h2
                        class="text-[2rem] leading-[1.12] font-extrabold tracking-tight sm:text-5xl lg:text-[3.5rem]"
                    >
                        {{ slide.heading }}
                    </h2>
                    <p
                        v-if="slide.subheading"
                        class="text-brand-100 mt-5 max-w-2xl text-lg leading-relaxed sm:text-xl"
                    >
                        {{ slide.subheading }}
                    </p>
                    <a
                        v-if="slide.cta_label && slide.cta_url"
                        :href="slide.cta_url"
                        class="group bg-gold-400 text-brand-950 shadow-raised hover:bg-gold-300 mt-8 inline-flex h-12 items-center gap-2 rounded-full px-6 font-bold transition-colors"
                    >
                        {{ slide.cta_label }}
                        <ArrowRight
                            class="ease-out-expo size-5 transition-transform duration-300 group-hover:translate-x-1"
                            aria-hidden="true"
                        />
                    </a>
                </div>
            </div>

            <form
                v-if="show_search && data?.search_url"
                :action="data.search_url"
                method="get"
                role="search"
                class="sm:bg-canvas sm:shadow-raised mt-8 flex max-w-xl flex-col gap-2 sm:flex-row sm:rounded-full sm:p-1.5"
            >
                <label :for="searchId" class="sr-only">Cari layanan</label>
                <div class="relative flex-1">
                    <Search
                        class="text-ink-muted pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2"
                        aria-hidden="true"
                    />
                    <input
                        :id="searchId"
                        type="search"
                        name="q"
                        maxlength="100"
                        autocomplete="off"
                        placeholder="Cari layanan, mis. surat keterangan"
                        class="bg-canvas text-ink shadow-raised placeholder:text-ink-muted h-12 w-full rounded-full pr-4 pl-12 text-base sm:shadow-none"
                    />
                </div>
                <button
                    type="submit"
                    class="bg-gold-400 text-brand-950 hover:bg-gold-300 h-12 flex-none rounded-full px-7 font-bold transition-colors"
                >
                    Cari
                </button>
            </form>

            <!-- Kontrol: tombol, titik slide, dan jeda. Panah kiri/kanan juga bekerja di sini. -->
            <div
                v-if="count > 1"
                class="mt-8 flex flex-wrap items-center gap-2"
                role="group"
                aria-label="Kontrol slide"
                @keydown="onControlsKeydown"
            >
                <button
                    v-if="canAutoplay"
                    type="button"
                    class="inline-grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/25 transition-colors hover:bg-white/20"
                    @click="pausedByUser = !pausedByUser"
                >
                    <Play
                        v-if="pausedByUser"
                        class="size-5"
                        aria-hidden="true"
                    />
                    <Pause v-else class="size-5" aria-hidden="true" />
                    <span class="sr-only">
                        {{
                            pausedByUser
                                ? 'Putar slide otomatis'
                                : 'Jeda slide otomatis'
                        }}
                    </span>
                </button>
                <button
                    type="button"
                    class="inline-grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/25 transition-colors hover:bg-white/20"
                    :aria-controls="carouselId"
                    @click="go(active - 1)"
                >
                    <ChevronLeft class="size-5" aria-hidden="true" />
                    <span class="sr-only">Slide sebelumnya</span>
                </button>
                <button
                    type="button"
                    class="inline-grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/25 transition-colors hover:bg-white/20"
                    :aria-controls="carouselId"
                    @click="go(active + 1)"
                >
                    <ChevronRight class="size-5" aria-hidden="true" />
                    <span class="sr-only">Slide berikutnya</span>
                </button>
                <div class="ml-1 flex items-center">
                    <button
                        v-for="(slide, index) in slides"
                        :key="index"
                        type="button"
                        class="group grid size-8 place-items-center rounded-full"
                        :aria-current="index === active ? 'true' : undefined"
                        @click="go(index)"
                    >
                        <span
                            class="block h-1.5 rounded-full transition-all duration-300"
                            :class="
                                index === active
                                    ? 'bg-gold-400 w-6'
                                    : 'w-2.5 bg-white/50 group-hover:bg-white/80'
                            "
                            aria-hidden="true"
                        />
                        <span class="sr-only">
                            Tampilkan slide {{ index + 1 }}: {{ slide.heading }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
