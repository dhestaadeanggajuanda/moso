<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\ArticleController@index.
-->
<script setup lang="ts">
import { computed } from 'vue';
import type { NewsItem } from '@/types/blocks';
import type { NewsCategoryFilter, Paginated, SiteContext } from '@/types/site';
import FilterChips from '../Partials/FilterChips.vue';
import NewsCard from '../Partials/NewsCard.vue';
import PageHero from '../Partials/PageHero.vue';
import PageNav from '../Partials/PageNav.vue';

const props = defineProps<{
    articles: Paginated<NewsItem>;
    categories: NewsCategoryFilter[];
    category: { name: string; slug: string } | null;
    site: SiteContext;
}>();

const trail = computed(() =>
    props.category
        ? [
              { label: 'Berita', href: props.site.links.news },
              { label: props.category.name },
          ]
        : [{ label: 'Berita' }],
);

const chips = computed(() => [
    {
        key: 'semua',
        label: 'Semua berita',
        href: props.site.links.news,
        active: props.category === null,
    },
    ...props.categories.map((item) => ({
        key: item.slug,
        label: item.name,
        href: item.url,
        active: item.slug === props.category?.slug,
    })),
]);
</script>

<template>
    <div>
        <PageHero
            :home-url="site.links.home"
            :title="category ? `Berita ${category.name}` : 'Berita Dinas'"
            description="Kabar kegiatan, program, dan pengumuman resmi dari dinas, terbaru lebih dulu."
            :trail="trail"
        />

        <section class="bg-mist py-10 sm:py-14" aria-labelledby="daftar-berita">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <FilterChips
                    v-if="categories.length"
                    label="Kategori berita"
                    :items="chips"
                    class="mb-8"
                />

                <h2
                    id="daftar-berita"
                    class="text-brand-950 mb-6 text-lg font-bold"
                >
                    <template v-if="articles.total">
                        {{ articles.total }} berita
                        <template v-if="articles.last_page > 1">
                            · menampilkan {{ articles.from }}–{{ articles.to }}
                        </template>
                    </template>
                    <template v-else>Belum ada berita</template>
                </h2>

                <ul
                    v-if="articles.data.length"
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <li v-for="item in articles.data" :key="item.id">
                        <NewsCard :item="item" />
                    </li>
                </ul>

                <div
                    v-else
                    class="rounded-card bg-canvas ring-line px-6 py-12 text-center ring-1"
                >
                    <p class="text-brand-950 text-lg font-bold">
                        <template v-if="category">
                            Belum ada berita di kategori ini.
                        </template>
                        <template v-else>
                            Berita dinas akan tampil di sini setelah terbit.
                        </template>
                    </p>
                    <a
                        v-if="category"
                        :href="site.links.news"
                        class="text-brand-700 mt-3 inline-flex min-h-11 items-center font-semibold hover:underline"
                    >
                        Lihat semua berita
                    </a>
                </div>

                <PageNav :paginator="articles" label="Halaman daftar berita" />
            </div>
        </section>
    </div>
</template>
