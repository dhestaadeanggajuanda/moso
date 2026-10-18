<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageRenderer from '@/Core/PageRenderer.vue';
import { themeComponent } from '@/Core/themes';
import type { HydratedBlock } from '@/types/blocks';
import type { PublicPage, SiteContext } from '@/types/site';

const props = defineProps<{
    page: PublicPage | null;
    blocks: HydratedBlock[];
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const title = computed(() => props.page?.title ?? props.site.name);
const description = computed(
    () => props.page?.meta_description ?? props.site.description ?? '',
);

/** Halaman tanpa banner tetap punya judul h1 dari layout tema. */
const heading = computed(() =>
    props.blocks.some((block) => block.type === 'HeroBanner')
        ? null
        : title.value,
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
        :heading="heading"
    >
        <PageRenderer :page-data="blocks" :theme="theme" />
    </component>
</template>
