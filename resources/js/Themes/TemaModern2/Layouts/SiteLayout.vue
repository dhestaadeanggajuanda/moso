<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan halaman inti
    (resources/js/pages/site/*).
-->
<script setup lang="ts">
/**
 * Kerangka semua halaman situs publik: skip link, header, isi, footer, dan
 * bilah navigasi bawah di ponsel. `heading` diisi bila halaman tidak punya
 * banner, supaya tetap ada judul h1.
 */
import type { SiteContext } from '@/types/site';
import MobileTabBar from '../Partials/MobileTabBar.vue';
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
    <div
        class="bg-ground text-ink flex min-h-dvh flex-col"
        data-tema="TemaModern2"
    >
        <a
            href="#konten"
            class="btn-action shadow-lift fixed top-3 left-3 z-50 -translate-y-24 focus:translate-y-0"
        >
            Lewati ke konten utama
        </a>

        <SiteHeader :site="site" :current-path="currentPath" />

        <main id="konten" tabindex="-1" class="flex-1 focus:outline-none">
            <div
                v-if="heading"
                class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8"
            >
                <nav aria-label="Remah roti" class="text-ink-muted text-sm">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <li>
                            <a
                                :href="site.links.home"
                                class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
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
                    class="text-ink max-w-4xl pt-1 pb-6 text-3xl leading-tight font-extrabold sm:text-4xl"
                >
                    {{ heading }}
                </h1>
            </div>

            <slot />
        </main>

        <SiteFooter :site="site" />
        <MobileTabBar :site="site" :current-path="currentPath" />
    </div>
</template>
