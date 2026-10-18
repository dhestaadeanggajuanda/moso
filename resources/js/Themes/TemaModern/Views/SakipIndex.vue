<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\SakipController@index.
-->
<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { computed, useId } from 'vue';
import type { SakipYearGroup, SiteContext } from '@/types/site';
import DocumentRow from '../Partials/DocumentRow.vue';
import PageHero from '../Partials/PageHero.vue';

const props = defineProps<{
    groups: SakipYearGroup[];
    total: number;
    filters: { year: number | null; type: string | null };
    yearOptions: number[];
    typeOptions: { value: string; label: string }[];
    site: SiteContext;
}>();

const yearId = useId();
const typeId = useId();

const isFiltered = computed(
    () => props.filters.year !== null || props.filters.type !== null,
);
</script>

<template>
    <div>
        <PageHero
            :home-url="site.links.home"
            title="E-SAKIP"
            description="Dokumen Sistem Akuntabilitas Kinerja Instansi Pemerintah: rencana strategis, rencana kerja, indikator kinerja, perjanjian kinerja, dan laporan kinerja dinas."
            :trail="[{ label: 'E-SAKIP' }]"
        />

        <section class="bg-mist py-10 sm:py-14" aria-labelledby="hasil-sakip">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form
                    :action="site.links.sakip"
                    method="get"
                    class="rounded-card bg-canvas ring-line grid gap-4 p-4 ring-1 shadow-card sm:grid-cols-[1fr_1.5fr_auto] sm:items-end sm:p-5"
                    aria-label="Saring dokumen E-SAKIP"
                >
                    <div class="grid gap-1.5">
                        <label
                            :for="yearId"
                            class="text-brand-950 text-sm font-semibold"
                        >
                            Tahun
                        </label>
                        <div class="relative">
                            <select
                                :id="yearId"
                                name="tahun"
                                class="bg-canvas text-ink border-line hover:border-brand-300 h-12 w-full appearance-none rounded-full border pr-11 pl-4 text-base"
                            >
                                <option
                                    value=""
                                    :selected="filters.year === null"
                                >
                                    Semua tahun
                                </option>
                                <option
                                    v-for="year in yearOptions"
                                    :key="year"
                                    :value="year"
                                    :selected="filters.year === year"
                                >
                                    {{ year }}
                                </option>
                            </select>
                            <ChevronDown
                                class="text-brand-600 pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2"
                                aria-hidden="true"
                            />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label
                            :for="typeId"
                            class="text-brand-950 text-sm font-semibold"
                        >
                            Jenis dokumen
                        </label>
                        <div class="relative">
                            <select
                                :id="typeId"
                                name="jenis"
                                class="bg-canvas text-ink border-line hover:border-brand-300 h-12 w-full appearance-none rounded-full border pr-11 pl-4 text-base"
                            >
                                <option
                                    value=""
                                    :selected="filters.type === null"
                                >
                                    Semua jenis
                                </option>
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    :selected="filters.type === option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="text-brand-600 pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2"
                                aria-hidden="true"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="bg-brand-700 hover:bg-brand-800 h-12 rounded-full px-7 font-bold text-white transition-colors"
                    >
                        Tampilkan
                    </button>
                </form>

                <div
                    class="mt-8 mb-2 flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"
                >
                    <h2
                        id="hasil-sakip"
                        class="text-brand-950 text-lg font-bold"
                    >
                        {{ total }} dokumen
                        <template v-if="isFiltered">sesuai pilihan</template>
                        <template v-else>diterbitkan</template>
                    </h2>
                    <a
                        v-if="isFiltered"
                        :href="site.links.sakip"
                        class="text-brand-700 inline-flex min-h-11 items-center font-semibold hover:underline"
                    >
                        Tampilkan semua dokumen
                    </a>
                </div>

                <template v-if="groups.length">
                    <section
                        v-for="group in groups"
                        :key="group.year"
                        class="mt-6"
                        :aria-labelledby="`sakip-${group.year}`"
                    >
                        <h3
                            :id="`sakip-${group.year}`"
                            class="text-brand-950 mb-4 flex items-baseline gap-3 text-2xl font-extrabold tracking-tight sm:text-3xl"
                        >
                            <span class="tabular-nums"
                                >Tahun {{ group.year }}</span
                            >
                            <span
                                class="text-ink-muted text-sm font-semibold tracking-normal"
                            >
                                {{ group.documents.length }} dokumen
                            </span>
                        </h3>
                        <ul
                            class="rounded-card bg-canvas ring-line divide-line divide-y overflow-hidden ring-1 shadow-card"
                        >
                            <li
                                v-for="document in group.documents"
                                :key="document.id"
                            >
                                <DocumentRow
                                    heading-level="h4"
                                    :title="document.title"
                                    :download-url="document.download_url"
                                    :file-size="document.file_size"
                                    :badge="document.type.label"
                                    :published-at="document.published_at"
                                />
                            </li>
                        </ul>
                    </section>
                </template>

                <div
                    v-else
                    class="rounded-card bg-canvas ring-line mt-4 px-6 py-12 text-center ring-1"
                >
                    <p class="text-brand-950 text-lg font-bold">
                        <template v-if="isFiltered">
                            Tidak ada dokumen untuk pilihan ini.
                        </template>
                        <template v-else>
                            Belum ada dokumen E-SAKIP yang diterbitkan.
                        </template>
                    </p>
                    <p
                        class="text-ink-muted mx-auto mt-2 max-w-md leading-relaxed"
                    >
                        Dokumen akuntabilitas kinerja diterbitkan setiap tahun
                        anggaran. Coba pilih tahun atau jenis dokumen lain.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
