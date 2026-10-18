<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Penanda asal berita: berita dinas sendiri atau Media Center Kota.
 * Dibedakan dengan ikon dan teks, bukan warna saja (WCAG 1.4.1).
 */
import { Landmark, RadioTower } from '@lucide/vue';
import type { NewsItem } from '@/types/blocks';

defineProps<{
    source: NewsItem['source'];
}>();
</script>

<template>
    <span
        v-if="source === 'media_center'"
        class="bg-mc-soft text-mc inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
    >
        <RadioTower class="size-3.5" aria-hidden="true" />
        Media Center
    </span>
    <span
        v-else
        class="bg-local-soft text-local inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
    >
        <Landmark class="size-3.5" aria-hidden="true" />
        Berita Dinas
    </span>
</template>
