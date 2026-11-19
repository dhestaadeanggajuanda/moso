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
                class="mt-6 flex max-w-2xl flex-col gap-3 sm:flex-row"
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
                        placeholder="Ketik nama layanan"
                        class="field pl-12"
                    />
                </div>
                <button type="submit" class="btn-action press flex-none">
                    Cari layanan
                </button>
            </form>
        </PageHero>

        <section class="pb-2" aria-labelledby="hasil-layanan">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                >
                    <h2
                        id="hasil-layanan"
                        class="text-ink text-lg font-extrabold"
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
                        class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
                    >
                        Tampilkan semua layanan
                    </a>
                </div>

                <ul
                    v-if="services.data.length"
                    class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 lg:gap-5"
                >
                    <li v-for="service in services.data" :key="service.id">
                        <ServiceCard :service="service" />
                    </li>
                </ul>

                <div
                    v-else
                    class="rounded-card bg-card px-6 py-12 text-center shadow-card"
                >
                    <p class="text-ink text-lg font-extrabold">
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
                        class="btn-action press mt-5"
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
