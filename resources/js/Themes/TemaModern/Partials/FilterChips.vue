<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Deret tautan filter berbentuk pil (kategori berita, kategori PPID). Filter
 * yang aktif ditandai aria-current, bukan warna saja.
 */
defineProps<{
    /** Label navigasi untuk pembaca layar, mis. "Kategori berita". */
    label: string;
    /** Kata benda jumlah untuk pembaca layar, mis. "dokumen". */
    countNoun?: string;
    items: {
        key: string;
        label: string;
        href: string;
        count?: number | null;
        active: boolean;
    }[];
}>();
</script>

<template>
    <nav :aria-label="label">
        <ul class="flex flex-wrap gap-2">
            <li v-for="item in items" :key="item.key">
                <a
                    :href="item.href"
                    :aria-current="item.active ? 'page' : undefined"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-[0.95rem] font-semibold ring-1 transition-colors duration-200"
                    :class="
                        item.active
                            ? 'bg-brand-700 ring-brand-700 text-white'
                            : 'bg-canvas text-brand-800 ring-line hover:ring-brand-300 hover:text-brand-700'
                    "
                >
                    {{ item.label }}
                    <span
                        v-if="item.count !== undefined && item.count !== null"
                        class="rounded-full px-2 py-0.5 text-sm tabular-nums"
                        :class="
                            item.active
                                ? 'bg-brand-800 text-white'
                                : 'bg-mist text-ink-muted'
                        "
                    >
                        <span class="sr-only">, </span>{{ item.count
                        }}<span v-if="countNoun" class="sr-only">
                            {{ countNoun }}</span
                        >
                    </span>
                </a>
            </li>
        </ul>
    </nav>
</template>
