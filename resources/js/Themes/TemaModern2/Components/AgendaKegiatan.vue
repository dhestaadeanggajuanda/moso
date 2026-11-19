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
    <section class="pt-8 sm:pt-14" :aria-labelledby="headingId">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader
                :title="title ?? 'Agenda Kegiatan'"
                :heading-id="headingId"
                description="Jadwal kegiatan dinas yang akan datang."
            />

            <ol v-if="items.length" class="grid gap-3 sm:gap-4 md:grid-cols-2">
                <li
                    v-for="agenda in items"
                    :key="agenda.id"
                    class="rounded-card flex gap-4 bg-card p-4 shadow-card sm:gap-5 sm:p-5"
                >
                    <time
                        :datetime="agenda.start_at"
                        class="bg-ground-deep text-ink rounded-icon flex w-16 flex-none flex-col items-center justify-center self-start py-2.5 sm:w-[4.5rem]"
                    >
                        <span
                            class="text-ink-muted mb-0.5 text-xs font-bold uppercase"
                            aria-hidden="true"
                        >
                            {{ agenda.date.weekdayShort }}
                        </span>
                        <span
                            class="text-2xl leading-none font-extrabold sm:text-3xl"
                            aria-hidden="true"
                        >
                            {{ agenda.date.day }}
                        </span>
                        <span
                            class="text-ink-muted mt-0.5 text-xs font-bold uppercase"
                            aria-hidden="true"
                        >
                            {{ agenda.date.month }}
                        </span>
                        <span class="sr-only">{{ agenda.fullDate }}</span>
                    </time>

                    <div class="min-w-0">
                        <h3
                            class="text-ink text-lg leading-snug font-extrabold"
                        >
                            {{ agenda.title }}
                        </h3>
                        <ul
                            class="text-ink-muted mt-3 grid gap-1.5 text-[0.95rem]"
                        >
                            <li class="flex items-start gap-2">
                                <Clock
                                    class="mt-0.5 size-4 flex-none"
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
                                    class="mt-0.5 size-4 flex-none"
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
                                    class="mt-0.5 size-4 flex-none"
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
                class="rounded-card text-ink-muted bg-card px-6 py-10 text-center shadow-card"
            >
                Belum ada agenda kegiatan terdekat.
            </p>
        </div>
    </section>
</template>
