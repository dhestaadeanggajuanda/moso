<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
import { CircleCheck, Timer, Wallet } from '@lucide/vue';
import type { ServiceSummary } from '@/types/blocks';

defineProps<{
    service: ServiceSummary;
    headingLevel?: 'h2' | 'h3';
}>();
</script>

<template>
    <article
        class="group press rounded-card hover:shadow-lift relative flex h-full flex-col bg-card p-5 shadow-card"
    >
        <component
            :is="headingLevel ?? 'h3'"
            class="text-ink group-hover:text-action text-lg leading-snug font-extrabold"
        >
            <a
                :href="service.url"
                class="focus-visible:after:rounded-card focus-visible:after:outline-ink after:absolute after:inset-0 after:content-[''] focus-visible:shadow-none focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:-outline-offset-3"
            >
                {{ service.name }}
            </a>
        </component>

        <p
            v-if="service.description"
            class="text-ink-muted mt-1.5 line-clamp-2 text-[0.95rem] leading-relaxed"
        >
            {{ service.description }}
        </p>

        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <dl class="flex flex-wrap gap-2 text-sm">
                <div class="bg-ground text-ink rounded-full px-3 py-1.5">
                    <dt class="sr-only">Jangka waktu</dt>
                    <dd class="inline-flex items-center gap-1.5">
                        <Timer
                            class="text-ink-muted size-4"
                            aria-hidden="true"
                        />
                        {{ service.processing_time }}
                    </dd>
                </div>
                <div
                    class="rounded-full px-3 py-1.5"
                    :class="
                        service.is_free
                            ? 'bg-ok-soft text-ok font-bold'
                            : 'bg-ground text-ink'
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
                            class="text-ink-muted size-4"
                            aria-hidden="true"
                        />
                        {{ service.cost }}
                    </dd>
                </div>
            </dl>

            <!-- Penanda bahwa kartu bisa ditekan; tautan aslinya ada di judul. -->
            <span
                class="bg-action group-hover:bg-action-hover inline-flex h-9 flex-none items-center rounded-full px-4 text-sm font-extrabold text-card transition-colors"
                aria-hidden="true"
            >
                Lihat
            </span>
        </div>
    </article>
</template>
