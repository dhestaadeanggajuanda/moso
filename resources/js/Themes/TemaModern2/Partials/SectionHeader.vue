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
        class="mb-5 flex flex-col gap-2 sm:mb-8 sm:flex-row sm:items-end sm:justify-between sm:gap-6"
    >
        <div class="max-w-2xl">
            <h2
                :id="headingId"
                class="text-ink text-xl leading-tight font-extrabold sm:text-3xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="text-ink-muted mt-1.5 text-base leading-relaxed"
            >
                {{ description }}
            </p>
        </div>
        <a
            v-if="actionLabel && actionHref"
            :href="actionHref"
            class="press group text-action hover:text-action-hover inline-flex min-h-11 flex-none items-center gap-2 self-start rounded-full font-extrabold sm:self-auto"
        >
            {{ actionLabel }}
            <ArrowRight
                class="ease-out-expo size-4 transition-transform duration-300 group-hover:translate-x-1"
                aria-hidden="true"
            />
        </a>
    </div>
</template>
