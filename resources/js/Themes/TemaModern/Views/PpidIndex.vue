<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\PublicDocumentController@index.
-->
<script setup lang="ts">
import { Mail, Phone } from '@lucide/vue';
import { computed } from 'vue';
import type {
    Paginated,
    PpidCategoryTab,
    PpidCategoryValue,
    PublicDocumentItem,
    SiteContext,
} from '@/types/site';
import DocumentRow from '../Partials/DocumentRow.vue';
import FilterChips from '../Partials/FilterChips.vue';
import PageHero from '../Partials/PageHero.vue';
import PageNav from '../Partials/PageNav.vue';

const props = defineProps<{
    documents: Paginated<PublicDocumentItem>;
    categories: PpidCategoryTab[];
    category: PpidCategoryValue | null;
    total: number;
    site: SiteContext;
}>();

const active = computed(
    () =>
        props.categories.find((item) => item.value === props.category) ?? null,
);

const chips = computed(() => [
    {
        key: 'semua',
        label: 'Semua',
        href: props.site.links.ppid,
        count: props.total,
        active: props.category === null,
    },
    ...props.categories.map((item) => ({
        key: item.value,
        label: item.label,
        href: item.url,
        count: item.count,
        active: item.value === props.category,
    })),
]);

const phoneHref = computed(() =>
    props.site.contact.phone
        ? `tel:${props.site.contact.phone.replace(/[^\d+]/g, '')}`
        : null,
);
</script>

<template>
    <div>
        <PageHero
            :home-url="site.links.home"
            title="Informasi Publik (PPID)"
            description="Dokumen yang wajib disediakan dinas menurut Undang-Undang Nomor 14 Tahun 2008 tentang Keterbukaan Informasi Publik. Unduh tanpa perlu mendaftar."
            :trail="[{ label: 'PPID' }]"
        />

        <section class="bg-mist py-10 sm:py-14" aria-labelledby="daftar-ppid">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <FilterChips
                    label="Kategori informasi publik"
                    count-noun="dokumen"
                    :items="chips"
                />

                <div class="mt-8 mb-5 max-w-3xl">
                    <h2
                        id="daftar-ppid"
                        class="text-brand-950 text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl"
                    >
                        {{ active ? active.label : 'Semua informasi publik' }}
                    </h2>
                    <p class="text-ink-muted mt-2 leading-relaxed">
                        <template v-if="active">{{
                            active.description
                        }}</template>
                        <template v-else>
                            Dokumen dikelompokkan dalam tiga kategori: berkala,
                            serta merta, dan setiap saat.
                        </template>
                    </p>
                </div>

                <ul
                    v-if="documents.data.length"
                    class="rounded-card bg-canvas ring-line divide-line divide-y overflow-hidden ring-1 shadow-card"
                >
                    <li v-for="document in documents.data" :key="document.slug">
                        <DocumentRow
                            :title="document.title"
                            :description="document.description"
                            :download-url="document.download_url"
                            :file-size="document.file_size"
                            :badge="active ? null : document.category.label"
                            :year="document.year"
                            :published-at="document.published_at"
                        />
                    </li>
                </ul>

                <div
                    v-else
                    class="rounded-card bg-canvas ring-line px-6 py-12 text-center ring-1"
                >
                    <p class="text-brand-950 text-lg font-bold">
                        Belum ada dokumen yang diterbitkan di sini.
                    </p>
                    <p
                        class="text-ink-muted mx-auto mt-2 max-w-md leading-relaxed"
                    >
                        Informasi yang Anda cari belum tersedia? Ajukan
                        permohonan informasi kepada petugas PPID dinas.
                    </p>
                    <div
                        v-if="phoneHref || site.contact.email"
                        class="mt-5 flex flex-wrap justify-center gap-3"
                    >
                        <a
                            v-if="phoneHref"
                            :href="phoneHref"
                            class="bg-brand-700 hover:bg-brand-800 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold text-white"
                        >
                            <Phone class="size-4" aria-hidden="true" />
                            <span class="tabular-nums">{{
                                site.contact.phone
                            }}</span>
                        </a>
                        <a
                            v-if="site.contact.email"
                            :href="`mailto:${site.contact.email}`"
                            class="bg-canvas text-brand-700 ring-line hover:ring-brand-300 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold ring-1"
                        >
                            <Mail class="size-4" aria-hidden="true" />
                            {{ site.contact.email }}
                        </a>
                    </div>
                </div>

                <PageNav
                    :paginator="documents"
                    label="Halaman daftar informasi publik"
                />
            </div>
        </section>
    </div>
</template>
