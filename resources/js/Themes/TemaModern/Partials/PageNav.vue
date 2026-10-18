<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Navigasi halaman sebelumnya/berikutnya untuk daftar berpaginasi.
 * Tidak tampil bila hanya ada satu halaman.
 */
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import type { Paginated } from '@/types/site';

defineProps<{
    paginator: Pick<
        Paginated<unknown>,
        'current_page' | 'last_page' | 'prev_page_url' | 'next_page_url'
    >;
    /** Label navigasi untuk pembaca layar, mis. "Halaman daftar berita". */
    label: string;
}>();
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        :aria-label="label"
        class="mt-10 flex items-center justify-between gap-4"
    >
        <a
            v-if="paginator.prev_page_url"
            :href="paginator.prev_page_url"
            rel="prev"
            class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold ring-1"
        >
            <ArrowLeft class="size-4" aria-hidden="true" />
            Sebelumnya
        </a>
        <span v-else />
        <p class="text-ink-muted text-sm tabular-nums">
            Halaman {{ paginator.current_page }} dari
            {{ paginator.last_page }}
        </p>
        <a
            v-if="paginator.next_page_url"
            :href="paginator.next_page_url"
            rel="next"
            class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold ring-1"
        >
            Berikutnya
            <ArrowRight class="size-4" aria-hidden="true" />
        </a>
        <span v-else />
    </nav>
</template>
