<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
    Judul dan ringkasan bisa berasal dari Media Center: tampilkan dengan {{ }},
    JANGAN pernah dengan v-html.
-->
<script setup lang="ts">
import { ExternalLink } from '@lucide/vue';
import { computed } from 'vue';
import type { NewsItem } from '@/types/blocks';
import { formatShortDate } from './format';
import SourceBadge from './SourceBadge.vue';

const props = defineProps<{
    item: NewsItem;
    featured?: boolean;
    /** Selalu kartu vertikal, juga di ponsel (dipakai di karusel). */
    stacked?: boolean;
}>();

/** Berita tanpa foto tampil sebagai kartu teks, bukan kotak gambar kosong. */
const hasImage = computed(() => !!props.item.image_url);

/** Kartu biasa di ponsel berbentuk baris rapat; kartu stacked tetap vertikal. */
const compact = computed(() => !props.featured && !props.stacked);
</script>

<template>
    <article
        class="group rounded-card bg-canvas ring-line ease-out-expo hover:shadow-raised relative flex h-full overflow-hidden ring-1 shadow-card transition-shadow duration-300"
        :class="
            featured
                ? hasImage
                    ? 'flex-col lg:grid lg:grid-cols-12'
                    : 'flex-col'
                : compact
                  ? 'flex-row sm:flex-col'
                  : 'flex-col'
        "
    >
        <div
            v-if="hasImage"
            class="bg-brand-50 relative overflow-hidden"
            :class="
                featured
                    ? 'aspect-[16/9] lg:col-span-7 lg:aspect-auto lg:min-h-80'
                    : compact
                      ? 'w-28 flex-none self-stretch sm:aspect-[16/10] sm:w-auto sm:self-auto'
                      : 'aspect-[16/10]'
            "
        >
            <img
                :src="item.image_url ?? undefined"
                :alt="item.image_alt ?? ''"
                loading="lazy"
                decoding="async"
                class="ease-out-expo absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
            />
        </div>

        <div
            class="flex min-w-0 flex-1 flex-col"
            :class="
                featured
                    ? [
                          'gap-3 p-5 sm:p-6',
                          hasImage
                              ? 'lg:col-span-5 lg:justify-center lg:p-8'
                              : 'lg:max-w-4xl lg:p-10',
                      ]
                    : compact
                      ? 'gap-2 p-4 sm:gap-3 sm:p-5'
                      : 'gap-3 p-5'
            "
        >
            <h3
                class="text-brand-950 group-hover:text-brand-700 leading-snug font-bold"
                :class="
                    featured
                        ? 'text-xl sm:text-2xl lg:text-[1.75rem] lg:leading-tight'
                        : 'line-clamp-3 text-base sm:text-lg'
                "
            >
                <!-- Seluruh kartu bisa diklik lewat pseudo-element tautan judul. -->
                <a
                    :href="item.url"
                    class="focus-visible:after:rounded-card focus-visible:after:outline-brand-700 after:absolute after:inset-0 after:content-[''] focus-visible:shadow-none focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:outline-offset-2"
                >
                    {{ item.title }}
                    <span v-if="item.source === 'media_center'" class="sr-only">
                        (situs Media Center Kota Singkawang)
                    </span>
                </a>
            </h3>

            <p
                v-if="item.excerpt"
                class="text-ink-muted text-[0.95rem] leading-relaxed"
                :class="
                    featured
                        ? 'line-clamp-4 sm:text-base'
                        : hasImage && compact
                          ? 'line-clamp-2 max-sm:hidden'
                          : 'line-clamp-2'
                "
            >
                {{ item.excerpt }}
            </p>

            <div
                class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-2 pt-1"
            >
                <SourceBadge :source="item.source" />
                <time
                    v-if="item.published_at"
                    :datetime="item.published_at"
                    class="text-ink-muted text-sm"
                >
                    {{ formatShortDate(item.published_at) }}
                </time>
                <span
                    v-if="item.source === 'media_center'"
                    class="text-mc inline-flex items-center gap-1 text-sm font-semibold"
                    aria-hidden="true"
                >
                    Baca di Media Center
                    <ExternalLink class="size-3.5" />
                </span>
            </div>
        </div>
    </article>
</template>
