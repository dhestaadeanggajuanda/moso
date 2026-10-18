<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan halaman inti
    (resources/js/pages/site/*).
-->
<script setup lang="ts">
/**
 * Kerangka semua halaman situs publik: skip link, header, isi, dan footer.
 * `heading` diisi bila halaman tidak punya banner, supaya tetap ada judul h1.
 */
import type { SiteContext } from '@/types/site';
import SiteFooter from '../Partials/SiteFooter.vue';
import SiteHeader from '../Partials/SiteHeader.vue';

defineProps<{
    site: SiteContext;
    pageTitle: string;
    currentPath?: string | null;
    heading?: string | null;
}>();
</script>

<template>
    <div class="bg-canvas text-ink flex min-h-dvh flex-col">
        <a
            href="#konten"
            class="bg-gold-400 text-brand-950 shadow-raised fixed top-3 left-3 z-50 -translate-y-24 rounded-full px-5 py-3 font-bold transition-transform focus:translate-y-0"
        >
            Lewati ke konten utama
        </a>

        <SiteHeader :site="site" :current-path="currentPath" />

        <main id="konten" tabindex="-1" class="flex-1 focus:outline-none">
            <div v-if="heading" class="border-line bg-mist border-b">
                <div
                    class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8"
                >
                    <nav aria-label="Remah roti" class="text-ink-muted text-sm">
                        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <li>
                                <a
                                    :href="site.links.home"
                                    class="text-brand-700 font-semibold hover:underline"
                                >
                                    Beranda
                                </a>
                            </li>
                            <li aria-hidden="true">/</li>
                            <li aria-current="page" class="truncate">
                                {{ heading }}
                            </li>
                        </ol>
                    </nav>
                    <h1
                        class="text-brand-950 mt-3 max-w-4xl text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl"
                    >
                        {{ heading }}
                    </h1>
                </div>
            </div>

            <slot />
        </main>

        <SiteFooter :site="site" />
    </div>
</template>
