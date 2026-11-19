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
/** "Hentikan animasi" di widget aksesibilitas (html[data-a11y-motion="off"]). */
const motionStoppedByWidget = ref(false);

const count = computed(() => props.slides.length);
const canAutoplay = computed(
    () =>
        props.autoplay &&
        count.value > 1 &&
        !reducedMotion.value &&
        !motionStoppedByWidget.value,
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

let motionObserver: MutationObserver | null = null;

onMounted(() => {
    const query = window.matchMedia('(prefers-reduced-motion: reduce)');
    reducedMotion.value = query.matches;
    query.addEventListener('change', (event) => {
        reducedMotion.value = event.matches;
    });

    const html = document.documentElement;
    const readWidget = () => {
        motionStoppedByWidget.value =
            html.getAttribute('data-a11y-motion') === 'off';
    };
    readWidget();
    motionObserver = new MutationObserver(readWidget);
    motionObserver.observe(html, {
        attributes: true,
        attributeFilter: ['data-a11y-motion'],
    });

    restartTimer();
});

onBeforeUnmount(() => {
    stopTimer();
    motionObserver?.disconnect();
});
</script>

<template>
    <section
        :id="carouselId"
        class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8"
        aria-roledescription="carousel"
        aria-label="Sorotan"
        @mouseenter="interacting = true"
        @mouseleave="interacting = false"
        @focusin="interacting = true"
        @focusout="onFocusOut"
    >
        <!-- Kartu sapaan putih; foto slide (bila ada) mengisi sisi kanan dan berganti dengan memudar. -->
        <div
            class="rounded-card grid overflow-hidden bg-card shadow-card"
            :class="(data?.images ?? []).some(Boolean) ? 'lg:grid-cols-12' : ''"
        >
            <!--
                Dengan foto: teks di kiri, foto di kanan. Tanpa foto di layar
                lebar: slide dan kontrol di kiri, kolom cari di kanan.
            -->
            <div
                class="flex flex-col justify-center p-5 sm:p-8 lg:p-10"
                :class="
                    (data?.images ?? []).some(Boolean)
                        ? 'lg:col-span-7'
                        : 'lg:grid lg:grid-cols-12 lg:items-center lg:gap-x-10'
                "
            >
                <!-- Semua slide di sel grid yang sama: tinggi mengikuti slide terpanjang, tanpa lompatan. -->
                <div
                    class="grid max-w-3xl [grid-template-areas:'slide']"
                    :class="
                        (data?.images ?? []).some(Boolean)
                            ? ''
                            : 'lg:col-span-7'
                    "
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
                            index === active
                                ? 'opacity-100'
                                : 'invisible opacity-0'
                        "
                    >
                        <h2
                            class="text-ink text-3xl leading-[1.15] font-extrabold sm:text-5xl"
                        >
                            {{ slide.heading }}
                        </h2>
                        <p
                            v-if="slide.subheading"
                            class="text-ink-muted mt-3 max-w-2xl text-base leading-relaxed sm:text-xl"
                        >
                            {{ slide.subheading }}
                        </p>
                        <a
                            v-if="slide.cta_label && slide.cta_url"
                            :href="slide.cta_url"
                            class="group"
                            :class="
                                show_search && data?.search_url
                                    ? 'text-action hover:text-action-hover mt-3 inline-flex min-h-11 items-center gap-2 font-extrabold'
                                    : 'btn-action press mt-6'
                            "
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
                    class="mt-6 flex max-w-xl flex-col gap-3 sm:flex-row"
                    :class="
                        (data?.images ?? []).some(Boolean)
                            ? ''
                            : 'lg:col-span-5 lg:col-start-8 lg:row-span-2 lg:row-start-1 lg:mt-0'
                    "
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
                            placeholder="Ketik nama layanan"
                            class="field pl-12"
                        />
                    </div>
                    <button type="submit" class="btn-action press flex-none">
                        Cari layanan
                    </button>
                </form>

                <!-- Kontrol: tombol, titik slide, dan jeda. Panah kiri/kanan juga bekerja di sini. -->
                <div
                    v-if="count > 1"
                    class="mt-6 flex flex-wrap items-center gap-2"
                    :class="
                        (data?.images ?? []).some(Boolean)
                            ? ''
                            : 'lg:col-span-7'
                    "
                    role="group"
                    aria-label="Kontrol slide"
                    @keydown="onControlsKeydown"
                >
                    <button
                        v-if="canAutoplay"
                        type="button"
                        class="press bg-ground-deep text-action hover:bg-action-100 inline-grid size-11 place-items-center rounded-full"
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
                        class="press bg-ground-deep text-action hover:bg-action-100 inline-grid size-11 place-items-center rounded-full"
                        :aria-controls="carouselId"
                        @click="go(active - 1)"
                    >
                        <ChevronLeft class="size-5" aria-hidden="true" />
                        <span class="sr-only">Slide sebelumnya</span>
                    </button>
                    <button
                        type="button"
                        class="press bg-ground-deep text-action hover:bg-action-100 inline-grid size-11 place-items-center rounded-full"
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
                            :aria-current="
                                index === active ? 'true' : undefined
                            "
                            @click="go(index)"
                        >
                            <span
                                class="block h-1.5 rounded-full transition-all duration-300"
                                :class="
                                    index === active
                                        ? 'bg-action w-6'
                                        : 'bg-edge group-hover:bg-ink-muted w-2.5'
                                "
                                aria-hidden="true"
                            />
                            <span class="sr-only">
                                Tampilkan slide {{ index + 1 }}:
                                {{ slide.heading }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="(data?.images ?? []).some(Boolean)"
                class="bg-ground-deep relative hidden aspect-[16/10] sm:block lg:col-span-5 lg:aspect-auto lg:min-h-[24rem]"
                aria-hidden="true"
            >
                <template
                    v-for="(image, index) in data?.images ?? []"
                    :key="index"
                >
                    <img
                        v-if="image"
                        :src="image"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        class="ease-out-expo absolute inset-0 size-full object-cover transition-opacity duration-700"
                        :class="index === active ? 'opacity-100' : 'opacity-0'"
                    />
                </template>
            </div>
        </div>
    </section>
</template>
