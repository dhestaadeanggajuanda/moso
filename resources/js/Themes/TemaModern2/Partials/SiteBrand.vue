<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Logo Pemkot + logo dinas (dari Pengaturan) dan nama situs. Bila logo belum
 * diunggah, tampil lambang netral; tema tidak pernah menanam logo dinas tertentu.
 */
import { Landmark } from '@lucide/vue';
import type { SiteContext } from '@/types/site';

defineProps<{
    site: SiteContext;
    size?: 'md' | 'lg';
}>();
</script>

<template>
    <a
        :href="site.links.home"
        class="press group rounded-control flex min-w-0 items-center gap-3"
    >
        <span class="flex flex-none items-center gap-2">
            <img
                v-if="site.logos.pemkot"
                :src="site.logos.pemkot"
                alt="Logo Pemerintah Kota Singkawang"
                class="h-10 w-auto sm:h-11"
                width="44"
                height="44"
            />
            <img
                v-if="site.logos.dinas"
                :src="site.logos.dinas"
                :alt="`Logo ${site.name}`"
                class="h-10 w-auto sm:h-11"
                width="44"
                height="44"
            />
            <span
                v-if="!site.logos.pemkot && !site.logos.dinas"
                class="bg-action-50 text-action rounded-icon grid size-11 place-items-center"
                aria-hidden="true"
            >
                <Landmark class="size-6" />
            </span>
        </span>
        <span class="min-w-0">
            <span
                class="text-ink block leading-tight font-extrabold"
                :class="
                    size === 'lg'
                        ? 'text-lg sm:text-xl'
                        : 'text-base sm:text-lg'
                "
            >
                {{ site.name }}
            </span>
            <span
                v-if="site.tagline"
                class="text-ink-muted mt-0.5 line-clamp-2 text-xs sm:text-sm"
            >
                {{ site.tagline }}
            </span>
        </span>
    </a>
</template>
