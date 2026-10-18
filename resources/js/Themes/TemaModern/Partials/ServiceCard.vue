<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
import { ArrowRight, CircleCheck, Timer, Wallet } from '@lucide/vue';
import type { ServiceSummary } from '@/types/blocks';

defineProps<{
    service: ServiceSummary;
    headingLevel?: 'h2' | 'h3';
}>();
</script>

<template>
    <article
        class="group rounded-card bg-canvas ring-line ease-out-expo hover:shadow-raised hover:ring-brand-300 relative flex h-full flex-col p-5 ring-1 shadow-card transition duration-300 sm:p-6"
    >
        <component
            :is="headingLevel ?? 'h3'"
            class="text-brand-950 group-hover:text-brand-700 text-lg leading-snug font-bold"
        >
            <a
                :href="service.url"
                class="focus-visible:after:rounded-card focus-visible:after:outline-brand-700 after:absolute after:inset-0 after:content-[''] focus-visible:shadow-none focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:outline-offset-2"
            >
                {{ service.name }}
            </a>
        </component>

        <p
            v-if="service.description"
            class="text-ink-muted mt-2 line-clamp-2 text-[0.95rem] leading-relaxed"
        >
            {{ service.description }}
        </p>

        <dl class="mt-auto flex flex-wrap gap-2 pt-5 text-sm">
            <div class="bg-mist text-ink rounded-full px-3 py-1.5">
                <dt class="sr-only">Jangka waktu</dt>
                <dd class="inline-flex items-center gap-1.5">
                    <Timer class="text-brand-600 size-4" aria-hidden="true" />
                    {{ service.processing_time }}
                </dd>
            </div>
            <div
                class="rounded-full px-3 py-1.5"
                :class="
                    service.is_free
                        ? 'bg-ok-soft text-ok font-semibold'
                        : 'bg-mist text-ink'
                "
            >
                <dt class="sr-only">Biaya</dt>
                <dd class="inline-flex items-center gap-1.5">
                    <CircleCheck
                        v-if="service.is_free"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <Wallet
                        v-else
                        class="text-brand-600 size-4"
                        aria-hidden="true"
                    />
                    {{ service.cost }}
                </dd>
            </div>
        </dl>

        <ArrowRight
            class="text-brand-300 ease-out-expo group-hover:text-brand-600 absolute top-6 right-5 size-5 opacity-0 transition duration-300 group-hover:translate-x-0.5 group-hover:opacity-100 sm:right-6"
            aria-hidden="true"
        />
    </article>
</template>
