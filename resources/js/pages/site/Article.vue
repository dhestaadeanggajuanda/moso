<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { themeComponent } from '@/Core/themes';
import type { PublicArticle, SiteContext } from '@/types/site';

const props = defineProps<{
    article: PublicArticle;
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const view = computed(() =>
    themeComponent(props.theme, 'Views', 'ArticleShow'),
);
const description = computed(
    () => props.article.excerpt ?? props.site.description ?? '',
);
</script>

<template>
    <Head :title="article.title">
        <meta name="description" :content="description" />
        <meta property="og:title" :content="article.title" />
        <meta property="og:description" :content="description" />
        <meta property="og:type" content="article" />
        <meta property="og:site_name" :content="site.name" />
        <meta
            v-if="article.image_url"
            property="og:image"
            :content="article.image_url"
        />
        <meta
            v-if="article.published_at"
            property="article:published_time"
            :content="article.published_at"
        />
    </Head>

    <component
        :is="layout"
        :site="site"
        :page-title="article.title"
        :current-path="inertiaPage.url"
    >
        <component :is="view" v-if="view" :article="article" :site="site" />
        <!-- Cadangan bila tema tidak menyediakan Views/ArticleShow.vue. -->
        <article v-else class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-2xl font-bold">{{ article.title }}</h1>
            <div v-html="article.body" />
        </article>
    </component>
</template>
