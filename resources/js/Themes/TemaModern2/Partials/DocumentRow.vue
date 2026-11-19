<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Satu baris dokumen unduhan (PPID, E-SAKIP) di dalam daftar berbingkai.
 * Seluruh baris dapat diklik lewat pseudo-element tautan judul; tautannya
 * menuju route unduhan aplikasi, bukan path storage.
 */
import { Download, FileText } from '@lucide/vue';
import { computed } from 'vue';
import { formatFileSize, formatShortDate } from './format';

const props = defineProps<{
    title: string;
    downloadUrl: string;
    fileSize: number;
    description?: string | null;
    /** Label jenis atau kategori dokumen. */
    badge?: string | null;
    year?: number | null;
    publishedAt?: string | null;
    headingLevel?: 'h2' | 'h3' | 'h4';
}>();

const size = computed(() => formatFileSize(props.fileSize));
</script>

<template>
    <article
        class="group hover:bg-ground/70 relative flex gap-4 p-4 transition-colors duration-200 sm:items-center sm:gap-5 sm:px-6 sm:py-5"
    >
        <span
            class="bg-action-50 text-action rounded-icon hidden size-12 flex-none place-items-center sm:grid"
            aria-hidden="true"
        >
            <FileText class="size-6" />
        </span>

        <div class="min-w-0 flex-1">
            <component
                :is="headingLevel ?? 'h3'"
                class="text-ink group-hover:text-action text-base leading-snug font-extrabold sm:text-lg"
            >
                <a
                    :href="downloadUrl"
                    class="focus-visible:after:outline-ink after:absolute after:inset-0 after:content-[''] focus-visible:shadow-none focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:-outline-offset-3"
                >
                    {{ title }}
                    <span class="sr-only">
                        (unduh PDF<template v-if="size">, {{ size }}</template
                        >)
                    </span>
                </a>
            </component>

            <p
                v-if="description"
                class="text-ink-muted mt-1 line-clamp-2 text-[0.95rem] leading-relaxed"
            >
                {{ description }}
            </p>

            <dl
                class="text-ink-muted mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm"
            >
                <div v-if="badge">
                    <dt class="sr-only">Jenis</dt>
                    <dd
                        class="bg-ground-deep text-ink rounded-full px-2.5 py-1 font-bold"
                    >
                        {{ badge }}
                    </dd>
                </div>
                <div v-if="year">
                    <dt class="sr-only">Tahun</dt>
                    <dd class="tabular-nums">Tahun {{ year }}</dd>
                </div>
                <div v-if="publishedAt">
                    <dt class="sr-only">Diterbitkan</dt>
                    <dd>
                        Terbit
                        <time :datetime="publishedAt">{{
                            formatShortDate(publishedAt)
                        }}</time>
                    </dd>
                </div>
            </dl>
        </div>

        <span
            class="bg-action group-hover:bg-action-hover inline-flex h-10 flex-none items-center gap-2 self-center rounded-full px-3 text-sm font-extrabold text-card transition-colors sm:px-4"
            aria-hidden="true"
        >
            <Download class="size-4" />
            <span class="max-sm:hidden">PDF</span>
            <span v-if="size" class="font-bold tabular-nums">
                {{ size }}
            </span>
        </span>
    </article>
</template>
