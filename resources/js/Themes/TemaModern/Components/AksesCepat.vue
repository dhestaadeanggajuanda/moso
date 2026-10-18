<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\AksesCepatBlock).
-->
<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import {
    CalendarDays,
    ClipboardList,
    FileChartColumn,
    FileText,
    MapPin,
    Megaphone,
    Newspaper,
    Phone,
} from '@lucide/vue';
import { computed, useId } from 'vue';
import type {
    AksesCepatIcon,
    AksesCepatProps,
    BlockDataMap,
} from '@/types/blocks';

const props = defineProps<
    AksesCepatProps & {
        data: BlockDataMap['AksesCepat'];
    }
>();

const icons: Record<AksesCepatIcon, LucideIcon> = {
    layanan: ClipboardList,
    pengaduan: Megaphone,
    dokumen: FileText,
    laporan: FileChartColumn,
    kontak: Phone,
    berita: Newspaper,
    agenda: CalendarDays,
    lokasi: MapPin,
};

const headingId = useId();

/** Tautan ke situs lain dibuka di tab baru. */
const links = computed(() =>
    props.items.map((item) => ({
        ...item,
        component: icons[item.icon] ?? ClipboardList,
        external: /^https?:\/\//i.test(item.url),
    })),
);
</script>

<template>
    <nav
        class="bg-canvas pt-5 pb-2 sm:pt-10 sm:pb-4"
        :aria-labelledby="title ? headingId : undefined"
        :aria-label="title ? undefined : 'Akses cepat'"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2
                v-if="title"
                :id="headingId"
                class="text-brand-950 mb-5 text-xl font-extrabold tracking-tight sm:text-2xl"
            >
                {{ title }}
            </h2>
            <ul
                class="grid gap-2 sm:gap-4"
                :class="
                    links.length >= 4
                        ? 'grid-cols-4'
                        : links.length === 3
                          ? 'grid-cols-3'
                          : 'grid-cols-2'
                "
            >
                <li
                    v-for="(link, index) in links"
                    :key="`${index}-${link.label}`"
                >
                    <a
                        :href="link.url"
                        :target="link.external ? '_blank' : undefined"
                        :rel="link.external ? 'noopener noreferrer' : undefined"
                        class="group rounded-card bg-canvas ring-line hover:ring-brand-300 hover:shadow-raised ease-out-expo flex h-full flex-col items-center justify-center gap-2 px-2 py-3 text-center ring-1 shadow-card transition duration-300 sm:min-h-20 sm:flex-row sm:justify-start sm:gap-4 sm:p-5 sm:text-left"
                    >
                        <span
                            class="bg-brand-50 text-brand-700 group-hover:bg-brand-700 grid size-10 flex-none place-items-center rounded-xl transition-colors duration-300 group-hover:text-white sm:size-12"
                            aria-hidden="true"
                        >
                            <component
                                :is="link.component"
                                class="size-5 sm:size-6"
                            />
                        </span>
                        <span
                            class="text-brand-950 group-hover:text-brand-700 text-sm leading-tight font-bold sm:text-base sm:leading-snug"
                        >
                            {{ link.label }}
                            <span v-if="link.external" class="sr-only">
                                (membuka tab baru)
                            </span>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</template>
