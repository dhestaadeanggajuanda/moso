<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { themeComponent } from '@/Core/themes';
import type { NewsItem } from '@/types/blocks';
import type { NewsCategoryFilter, Paginated, SiteContext } from '@/types/site';

const props = defineProps<{
    articles: Paginated<NewsItem>;
    categories: NewsCategoryFilter[];
    category: { name: string; slug: string } | null;
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const view = computed(() =>
    themeComponent(props.theme, 'Views', 'ArticleIndex'),
);
const title = computed(() => {
    const base = props.category ? `Berita ${props.category.name}` : 'Berita';

    return props.articles.current_page > 1
        ? `${base} (halaman ${props.articles.current_page})`
        : base;
});
const description = computed(
    () => props.site.description ?? 'Kabar terbaru dari kegiatan dinas.',
);
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" :content="site.name" />
    </Head>

    <component
        :is="layout"
        :site="site"
        :page-title="title"
        :current-path="inertiaPage.url"
    >
        <component
            :is="view"
            v-if="view"
            :articles="articles"
            :categories="categories"
            :category="category"
            :site="site"
        />
        <!-- Cadangan bila tema tidak menyediakan Views/ArticleIndex.vue. -->
        <section v-else class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-2xl font-bold">Berita</h1>
            <ul class="mt-4 list-disc pl-5">
                <li v-for="item in articles.data" :key="item.id">
                    <a :href="item.url" class="underline">{{ item.title }}</a>
                </li>
            </ul>
        </section>
    </component>
</template>
