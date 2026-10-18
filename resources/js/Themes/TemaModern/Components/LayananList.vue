<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\LayananListBlock).
-->
<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, useId } from 'vue';
import type { BlockDataMap, LayananListProps } from '@/types/blocks';
import SectionHeader from '../Partials/SectionHeader.vue';
import ServiceCard from '../Partials/ServiceCard.vue';

const props = defineProps<
    LayananListProps & {
        data: BlockDataMap['LayananList'];
    }
>();

const headingId = useId();
const searchId = useId();
const items = computed(() => props.data?.items ?? []);
const hasMore = computed(() => (props.data?.total ?? 0) > items.value.length);
</script>

<template>
    <section
        class="bg-canvas pt-10 pb-14 sm:py-20"
        :aria-labelledby="headingId"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader
                :title="title ?? 'Layanan Publik'"
                :heading-id="headingId"
                description="Persyaratan, jangka waktu, dan biaya setiap layanan, supaya Anda siap sebelum datang ke kantor."
                :action-label="
                    hasMore ? `Lihat semua ${data?.total} layanan` : null
                "
                :action-href="data?.index_url"
            />

            <form
                v-if="show_search && data?.index_url"
                :action="data.index_url"
                method="get"
                role="search"
                class="mb-8 flex max-w-2xl flex-col gap-2 sm:flex-row"
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
                        placeholder="Ketik nama layanan"
                        class="border-line bg-canvas text-ink placeholder:text-ink-muted hover:border-brand-300 h-12 w-full rounded-full border pr-4 pl-12 text-base"
                    />
                </div>
                <button
                    type="submit"
                    class="bg-brand-700 hover:bg-brand-800 h-12 flex-none rounded-full px-7 font-bold text-white transition-colors"
                >
                    Cari
                </button>
            </form>

            <ul
                v-if="items.length"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5"
            >
                <li v-for="service in items" :key="service.id">
                    <ServiceCard :service="service" />
                </li>
            </ul>

            <p
                v-else
                class="rounded-card bg-mist text-ink-muted px-6 py-10 text-center"
            >
                Daftar layanan sedang disiapkan. Untuk sementara, silakan
                hubungi kami melalui kontak di bagian bawah halaman.
            </p>
        </div>
    </section>
</template>
