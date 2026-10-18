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
        class="relative isolate overflow-hidden"
        :class="
            variant === 'muted'
                ? 'border-line bg-mist text-ink border-b'
                : 'bg-brand-900 text-white'
        "
        :aria-labelledby="headingId"
    >
        <!-- Foto latar (varian utama) dengan lapisan gelap agar teks putih tetap kontras. -->
        <template v-if="variant !== 'muted' && data?.image_url">
            <img
                :src="data.image_url"
                :alt="image_alt ?? ''"
                fetchpriority="high"
                decoding="async"
                class="absolute inset-0 -z-20 size-full object-cover"
            />
            <div
                class="from-brand-950/95 via-brand-950/75 to-brand-950/35 lg:from-brand-950/92 lg:via-brand-950/70 lg:to-brand-950/10 absolute inset-0 -z-10 bg-linear-to-t lg:bg-linear-to-r"
                aria-hidden="true"
            />
        </template>

        <div
            class="mx-auto flex max-w-7xl px-4 sm:px-6 lg:px-8"
            :class="
                variant !== 'muted' && data?.image_url
                    ? 'min-h-[26rem] items-end pt-16 pb-12 sm:min-h-[30rem] sm:pb-16 lg:min-h-[34rem] lg:items-center lg:py-24'
                    : 'py-14 sm:py-20 lg:py-24'
            "
        >
            <div class="w-full max-w-3xl">
                <h1
                    :id="headingId"
                    class="text-[2rem] leading-[1.12] font-extrabold tracking-tight sm:text-5xl lg:text-[3.5rem]"
                    :class="variant === 'muted' ? 'text-brand-950' : ''"
                >
                    {{ heading }}
                </h1>
                <p
                    v-if="subheading"
                    class="mt-5 max-w-2xl text-lg leading-relaxed sm:text-xl"
                    :class="
                        variant === 'muted'
                            ? 'text-ink-muted'
                            : 'text-brand-100'
                    "
                >
                    {{ subheading }}
                </p>

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
                        class="h-12 flex-none rounded-full px-7 font-bold transition-colors"
                        :class="
                            variant === 'muted'
                                ? 'bg-brand-700 hover:bg-brand-800 text-white'
                                : 'bg-gold-400 text-brand-950 hover:bg-gold-300'
                        "
                    >
                        Cari
                    </button>
                </form>

                <a
                    v-if="cta_label && cta_url"
                    :href="cta_url"
                    class="group inline-flex items-center gap-2 font-bold transition-colors"
                    :class="
                        show_search && data?.search_url
                            ? [
                                  'mt-5 min-h-11 underline decoration-2 underline-offset-[0.3em]',
                                  variant === 'muted'
                                      ? 'text-brand-700 decoration-brand-300 hover:text-brand-900'
                                      : 'decoration-gold-400 hover:text-gold-200 text-white',
                              ]
                            : [
                                  'shadow-raised mt-8 h-12 rounded-full px-6',
                                  variant === 'muted'
                                      ? 'bg-brand-700 hover:bg-brand-800 text-white'
                                      : 'bg-gold-400 text-brand-950 hover:bg-gold-300',
                              ]
                    "
                >
                    {{ cta_label }}
                    <ArrowRight
                        class="ease-out-expo size-5 transition-transform duration-300 group-hover:translate-x-1"
                        aria-hidden="true"
                    />
                </a>
            </div>
        </div>
    </section>
</template>
