<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\HeroBannerBlock).
-->
<script setup lang="ts">
import { ArrowRight, Search } from '@lucide/vue';
import { useId } from 'vue';
import type { BlockDataMap, HeroBannerProps } from '@/types/blocks';

defineProps<
    HeroBannerProps & {
        data: BlockDataMap['HeroBanner'];
    }
>();

const headingId = useId();
const searchId = useId();
</script>

<template>
    <section
        class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8"
        :aria-labelledby="headingId"
    >
        <!-- Varian utama: kartu sapaan putih; foto (bila ada) mengisi sisi kanan. -->
        <div
            class="grid overflow-hidden"
            :class="[
                variant === 'muted'
                    ? 'py-2 sm:py-6'
                    : 'rounded-card bg-card shadow-card',
                variant !== 'muted' && data?.image_url ? 'lg:grid-cols-12' : '',
            ]"
        >
            <!--
                Dengan foto: teks di kiri, foto di kanan. Tanpa foto di layar
                lebar: judul di kiri, kolom cari di kanan, sejajar di tengah.
            -->
            <div
                class="flex flex-col justify-center"
                :class="[
                    variant === 'muted' ? '' : 'p-5 sm:p-8 lg:p-10',
                    variant !== 'muted' && data?.image_url
                        ? 'lg:col-span-7'
                        : 'lg:grid lg:grid-cols-12 lg:items-center lg:gap-x-10',
                ]"
            >
                <h1
                    :id="headingId"
                    class="text-ink text-3xl leading-[1.15] font-extrabold sm:text-5xl"
                    :class="
                        variant !== 'muted' && data?.image_url
                            ? ''
                            : 'lg:col-span-7'
                    "
                >
                    {{ heading }}
                </h1>
                <p
                    v-if="subheading"
                    class="text-ink-muted mt-3 max-w-2xl text-base leading-relaxed sm:text-xl"
                    :class="
                        variant !== 'muted' && data?.image_url
                            ? ''
                            : 'lg:col-span-7'
                    "
                >
                    {{ subheading }}
                </p>

                <form
                    v-if="show_search && data?.search_url"
                    :action="data.search_url"
                    method="get"
                    role="search"
                    class="mt-6 flex max-w-xl flex-col gap-3 sm:flex-row"
                    :class="
                        variant !== 'muted' && data?.image_url
                            ? ''
                            : 'lg:col-span-5 lg:col-start-8 lg:row-span-3 lg:row-start-1 lg:mt-0'
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

                <a
                    v-if="cta_label && cta_url"
                    :href="cta_url"
                    class="group self-start lg:justify-self-start"
                    :class="[
                        show_search && data?.search_url
                            ? 'text-action hover:text-action-hover mt-3 inline-flex min-h-11 items-center gap-2 font-extrabold'
                            : 'btn-action press mt-6',
                        variant !== 'muted' && data?.image_url
                            ? ''
                            : 'lg:col-span-7',
                    ]"
                >
                    {{ cta_label }}
                    <ArrowRight
                        class="ease-out-expo size-5 transition-transform duration-300 group-hover:translate-x-1"
                        aria-hidden="true"
                    />
                </a>
            </div>

            <div
                v-if="variant !== 'muted' && data?.image_url"
                class="bg-ground-deep relative hidden aspect-[16/10] sm:block lg:col-span-5 lg:aspect-auto lg:min-h-[22rem]"
            >
                <!-- Disembunyikan di ponsel supaya sapaan, kolom cari, dan petak aksi tampil di layar pertama; lazy agar tidak diunduh di sana. -->
                <img
                    :src="data.image_url"
                    :alt="image_alt ?? ''"
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 size-full object-cover"
                />
            </div>
        </div>
    </section>
</template>
