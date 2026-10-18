<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';

defineProps<{
    title: string;
    headingId: string;
    description?: string | null;
    actionLabel?: string | null;
    actionHref?: string | null;
}>();
</script>

<template>
    <div
        class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-6"
    >
        <div class="max-w-2xl">
            <h2
                :id="headingId"
                class="text-brand-950 text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="text-ink-muted mt-2 text-base leading-relaxed"
            >
                {{ description }}
            </p>
        </div>
        <a
            v-if="actionLabel && actionHref"
            :href="actionHref"
            class="group text-brand-700 hover:text-brand-900 inline-flex min-h-11 flex-none items-center gap-2 self-start rounded-full px-1 font-bold sm:self-auto"
        >
            {{ actionLabel }}
            <ArrowRight
                class="ease-out-expo size-4 transition-transform duration-300 group-hover:translate-x-1"
                aria-hidden="true"
            />
        </a>
    </div>
</template>
