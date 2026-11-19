<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logika penanda halaman aktif
    adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Bilah navigasi bawah khusus ponsel: empat tujuan utama dari `site.links`.
 * Halaman aktif ditandai aria-current, bentuk kapsul, dan huruf tebal, bukan
 * warna saja. Menu lengkap tetap ada di tombol Menu pada header.
 */
import { ClipboardList, House, Newspaper, Phone } from '@lucide/vue';
import { computed } from 'vue';
import type { SiteContext } from '@/types/site';
import { pathOf } from './format';

const props = defineProps<{
    site: SiteContext;
    currentPath?: string | null;
}>();

const currentPathname = computed(() =>
    pathOf(props.currentPath, props.site.links.home),
);

function isHere(href: string, exact: boolean): boolean {
    const path = pathOf(href, props.site.links.home);
    const current = currentPathname.value;

    if (path === null || current === null) {
        return false;
    }

    return exact
        ? path === current
        : current === path || current.startsWith(`${path}/`);
}

const tabs = computed(() => [
    {
        key: 'beranda',
        label: 'Beranda',
        href: props.site.links.home,
        icon: House,
        here: isHere(props.site.links.home, true),
    },
    {
        key: 'layanan',
        label: 'Layanan',
        href: props.site.links.services,
        icon: ClipboardList,
        here: isHere(props.site.links.services, false),
    },
    {
        key: 'berita',
        label: 'Berita',
        href: props.site.links.news,
        icon: Newspaper,
        here: isHere(props.site.links.news, false),
    },
    {
        key: 'kontak',
        label: 'Kontak',
        href: '#kontak',
        icon: Phone,
        here: false,
    },
]);
</script>

<template>
    <nav
        aria-label="Navigasi cepat"
        class="shadow-bar fixed inset-x-0 bottom-0 z-40 rounded-t-[1.75rem] bg-card pb-[env(safe-area-inset-bottom)] lg:hidden"
    >
        <ul class="mx-auto grid max-w-md grid-cols-4">
            <li v-for="tab in tabs" :key="tab.key">
                <a
                    :href="tab.href"
                    :aria-current="tab.here ? 'page' : undefined"
                    class="press flex min-h-[4.5rem] flex-col items-center justify-center gap-1 text-xs"
                    :class="
                        tab.here
                            ? 'text-action font-extrabold'
                            : 'text-ink-muted hover:text-ink font-bold'
                    "
                >
                    <span
                        class="grid h-8 w-14 place-items-center rounded-full transition-colors"
                        :class="tab.here ? 'bg-action text-card' : ''"
                        aria-hidden="true"
                    >
                        <component :is="tab.icon" class="size-5" />
                    </span>
                    {{ tab.label }}
                </a>
            </li>
        </ul>
    </nav>
</template>
