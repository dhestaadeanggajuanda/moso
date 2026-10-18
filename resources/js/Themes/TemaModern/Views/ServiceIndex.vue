<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\ServiceController@index.
-->
<script setup lang="ts">
import { Phone, Search } from '@lucide/vue';
import { useId } from 'vue';
import type { ServiceSummary } from '@/types/blocks';
import type { Paginated, SiteContext } from '@/types/site';
import PageHero from '../Partials/PageHero.vue';
import PageNav from '../Partials/PageNav.vue';
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
        <PageHero
            :home-url="site.links.home"
            title="Layanan Publik"
            description="Temukan persyaratan, prosedur, jangka waktu, dan biaya setiap layanan sebelum Anda datang ke kantor."
            :trail="[{ label: 'Layanan' }]"
        >
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
        </PageHero>

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

                <PageNav :paginator="services" label="Halaman daftar layanan" />
            </div>
        </section>
    </div>
</template>
