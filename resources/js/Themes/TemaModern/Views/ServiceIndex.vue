<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\ServiceController@index.
-->
<script setup lang="ts">
import { ArrowLeft, ArrowRight, Phone, Search } from '@lucide/vue';
import { useId } from 'vue';
import type { ServiceSummary } from '@/types/blocks';
import type { Paginated, SiteContext } from '@/types/site';
import ServiceCard from '../Partials/ServiceCard.vue';

defineProps<{
    services: Paginated<ServiceSummary>;
    search: string;
    site: SiteContext;
}>();

const searchId = useId();
</script>

<template>
    <div>
        <header
            class="bg-brand-900 relative isolate overflow-hidden text-white"
        >
            <div
                class="mx-auto max-w-7xl px-4 pt-8 pb-12 sm:px-6 sm:pt-12 sm:pb-16 lg:px-8"
            >
                <nav aria-label="Remah roti" class="text-brand-200 text-sm">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <li>
                            <a
                                :href="site.links.home"
                                class="font-semibold text-white hover:underline"
                            >
                                Beranda
                            </a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">Layanan</li>
                    </ol>
                </nav>
                <h1
                    class="mt-4 text-3xl leading-tight font-extrabold tracking-tight sm:text-5xl"
                >
                    Layanan Publik
                </h1>
                <p
                    class="text-brand-100 mt-4 max-w-2xl text-lg leading-relaxed"
                >
                    Temukan persyaratan, prosedur, jangka waktu, dan biaya
                    setiap layanan sebelum Anda datang ke kantor.
                </p>

                <form
                    :action="site.links.services"
                    method="get"
                    role="search"
                    class="sm:bg-canvas sm:shadow-raised mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row sm:rounded-full sm:p-1.5"
                >
                    <label :for="searchId" class="sr-only">Cari layanan</label>
                    <div class="relative flex-1">
                        <Search
                            class="text-ink-muted pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <input
                            :id="searchId"
                            type="search"
                            name="q"
                            maxlength="100"
                            autocomplete="off"
                            :value="search"
                            placeholder="Cari layanan, mis. surat keterangan"
                            class="bg-canvas text-ink shadow-raised placeholder:text-ink-muted h-12 w-full rounded-full pr-4 pl-12 text-base sm:shadow-none"
                        />
                    </div>
                    <button
                        type="submit"
                        class="bg-gold-400 text-brand-950 hover:bg-gold-300 h-12 flex-none rounded-full px-7 font-bold transition-colors"
                    >
                        Cari
                    </button>
                </form>
            </div>
        </header>

        <section class="bg-mist py-10 sm:py-14" aria-labelledby="hasil-layanan">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"
                >
                    <h2
                        id="hasil-layanan"
                        class="text-brand-950 text-lg font-bold"
                    >
                        <template v-if="search">
                            {{ services.total }} layanan cocok dengan “{{
                                search
                            }}”
                        </template>
                        <template v-else
                            >{{ services.total }} layanan tersedia</template
                        >
                    </h2>
                    <a
                        v-if="search"
                        :href="site.links.services"
                        class="text-brand-700 inline-flex min-h-11 items-center font-semibold hover:underline"
                    >
                        Tampilkan semua layanan
                    </a>
                </div>

                <ul
                    v-if="services.data.length"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5"
                >
                    <li v-for="service in services.data" :key="service.id">
                        <ServiceCard :service="service" />
                    </li>
                </ul>

                <div
                    v-else
                    class="rounded-card bg-canvas ring-line px-6 py-12 text-center ring-1"
                >
                    <p class="text-brand-950 text-lg font-bold">
                        Layanan tidak ditemukan.
                    </p>
                    <p
                        class="text-ink-muted mx-auto mt-2 max-w-md leading-relaxed"
                    >
                        Coba kata lain yang lebih umum, atau tanyakan langsung
                        kepada petugas kami.
                    </p>
                    <a
                        v-if="site.contact.phone"
                        :href="`tel:${site.contact.phone.replace(/[^\d+]/g, '')}`"
                        class="bg-brand-700 hover:bg-brand-800 mt-5 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold text-white"
                    >
                        <Phone class="size-4" aria-hidden="true" />
                        <span class="tabular-nums">{{
                            site.contact.phone
                        }}</span>
                    </a>
                </div>

                <nav
                    v-if="services.last_page > 1"
                    aria-label="Halaman daftar layanan"
                    class="mt-10 flex items-center justify-between gap-4"
                >
                    <a
                        v-if="services.prev_page_url"
                        :href="services.prev_page_url"
                        rel="prev"
                        class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold ring-1"
                    >
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        Sebelumnya
                    </a>
                    <span v-else />
                    <p class="text-ink-muted text-sm tabular-nums">
                        Halaman {{ services.current_page }} dari
                        {{ services.last_page }}
                    </p>
                    <a
                        v-if="services.next_page_url"
                        :href="services.next_page_url"
                        rel="next"
                        class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold ring-1"
                    >
                        Berikutnya
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </a>
                    <span v-else />
                </nav>
            </div>
        </section>
    </div>
</template>
