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
        class="pt-4 sm:pt-6"
        :aria-labelledby="title ? headingId : undefined"
        :aria-label="title ? undefined : 'Akses cepat'"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2
                v-if="title"
                :id="headingId"
                class="text-ink mb-4 text-xl font-extrabold sm:text-2xl"
            >
                {{ title }}
            </h2>
            <!--
                Ponsel: petak ikon di atas label agar kata panjang tetap utuh.
                Tiga tautan satu baris, ikon pindah ke samping label mulai md.
                Jumlah lain dua kolom, ikon di samping label mulai sm, dan
                empat kolom mulai lg bila ada empat tautan atau lebih.
            -->
            <ul
                class="grid gap-3 sm:gap-4"
                :class="
                    links.length === 3
                        ? 'grid-cols-3'
                        : links.length >= 4
                          ? 'grid-cols-2 lg:grid-cols-4'
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
                        class="group press rounded-tile hover:shadow-lift flex h-full flex-col items-center gap-2 bg-card px-2 py-4 text-center shadow-card"
                        :class="
                            links.length === 3
                                ? 'sm:p-4 md:min-h-20 md:flex-row md:gap-4 md:p-5 md:text-left'
                                : 'sm:min-h-20 sm:flex-row sm:gap-4 sm:p-5 sm:text-left'
                        "
                    >
                        <span
                            class="bg-action-50 text-action group-hover:bg-action-100 rounded-icon grid size-11 flex-none place-items-center transition-colors sm:size-12"
                            aria-hidden="true"
                        >
                            <component :is="link.component" class="size-6" />
                        </span>
                        <span
                            class="text-ink min-w-0 text-sm leading-tight font-extrabold wrap-anywhere sm:text-base sm:leading-snug"
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
