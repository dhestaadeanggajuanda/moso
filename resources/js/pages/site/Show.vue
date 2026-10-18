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

/** Banner pertama di halaman (HeroBanner membawa h1 sendiri; slide HeroSlider memakai h2). */
const firstHero = computed(() =>
    props.blocks.find(
        (block) => block.type === 'HeroBanner' || block.type === 'HeroSlider',
    ),
);

/** Halaman tanpa banner tetap punya judul h1 dari layout tema. */
const heading = computed(() => (firstHero.value ? null : title.value));

/** Halaman yang dibuka HeroSlider mendapat h1 khusus pembaca layar. */
const screenReaderHeading = computed(() =>
    firstHero.value?.type === 'HeroSlider' ? title.value : null,
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
        <h1 v-if="screenReaderHeading" class="sr-only">
            {{ screenReaderHeading }}
        </h1>
        <PageRenderer :page-data="blocks" :theme="theme" />
    </component>
</template>
