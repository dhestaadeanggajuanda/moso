<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\AgendaKegiatanBlock).
-->
<script setup lang="ts">
import { Clock, Landmark, MapPin } from '@lucide/vue';
import { computed, useId } from 'vue';
import type { AgendaKegiatanProps, BlockDataMap } from '@/types/blocks';
import { dateParts, formatDate, formatTimeRange } from '../Partials/format';
import SectionHeader from '../Partials/SectionHeader.vue';

const props = defineProps<
    AgendaKegiatanProps & {
        data: BlockDataMap['AgendaKegiatan'];
    }
>();

const headingId = useId();

const items = computed(() =>
    (props.data?.items ?? []).map((agenda) => ({
        ...agenda,
        date: dateParts(agenda.start_at),
        fullDate: formatDate(agenda.start_at),
        time: formatTimeRange(agenda.start_at, agenda.end_at),
    })),
);
</script>

<template>
    <section class="bg-canvas py-14 sm:py-20" :aria-labelledby="headingId">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader
                :title="title ?? 'Agenda Kegiatan'"
                :heading-id="headingId"
                description="Jadwal kegiatan dinas yang akan datang."
            />

            <ol v-if="items.length" class="grid gap-4 md:grid-cols-2">
                <li
                    v-for="agenda in items"
                    :key="agenda.id"
                    class="rounded-card bg-canvas ring-line flex gap-4 p-4 ring-1 shadow-card sm:gap-5 sm:p-5"
                >
                    <time
                        :datetime="agenda.start_at"
                        class="bg-brand-900 flex w-16 flex-none flex-col items-center justify-center self-start rounded-xl py-2.5 text-white sm:w-[4.5rem]"
                    >
                        <span
                            class="text-brand-200 mb-1 text-xs font-semibold uppercase"
                            aria-hidden="true"
                        >
                            {{ agenda.date.weekdayShort }}
                        </span>
                        <span
                            class="text-2xl leading-none font-extrabold sm:text-[1.75rem]"
                            aria-hidden="true"
                        >
                            {{ agenda.date.day }}
                        </span>
                        <span
                            class="text-brand-200 mt-1 text-xs font-semibold uppercase"
                            aria-hidden="true"
                        >
                            {{ agenda.date.month }}
                        </span>
                        <span class="sr-only">{{ agenda.fullDate }}</span>
                    </time>

                    <div class="min-w-0">
                        <h3
                            class="text-brand-950 text-lg leading-snug font-bold"
                        >
                            {{ agenda.title }}
                        </h3>
                        <ul
                            class="text-ink-muted mt-3 grid gap-1.5 text-[0.95rem]"
                        >
                            <li class="flex items-start gap-2">
                                <Clock
                                    class="text-brand-600 mt-0.5 size-4 flex-none"
                                    aria-hidden="true"
                                />
                                <span class="tabular-nums">
                                    {{ agenda.date.weekday }}, {{ agenda.time }}
                                </span>
                            </li>
                            <li
                                v-if="agenda.location"
                                class="flex items-start gap-2"
                            >
                                <MapPin
                                    class="text-brand-600 mt-0.5 size-4 flex-none"
                                    aria-hidden="true"
                                />
                                <span>
                                    <span class="sr-only">Lokasi: </span>
                                    {{ agenda.location }}
                                </span>
                            </li>
                            <li
                                v-if="agenda.organizer"
                                class="flex items-start gap-2"
                            >
                                <Landmark
                                    class="text-brand-600 mt-0.5 size-4 flex-none"
                                    aria-hidden="true"
                                />
                                <span>
                                    <span class="sr-only">Penyelenggara: </span>
                                    {{ agenda.organizer }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </li>
            </ol>

            <p
                v-else
                class="rounded-card bg-mist text-ink-muted px-6 py-10 text-center"
            >
                Belum ada agenda kegiatan terdekat.
            </p>
        </div>
    </section>
</template>
