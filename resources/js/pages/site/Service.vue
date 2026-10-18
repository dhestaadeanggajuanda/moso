<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { themeComponent } from '@/Core/themes';
import type { PublicService, SiteContext } from '@/types/site';

const props = defineProps<{
    service: PublicService;
    index_url: string;
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const view = computed(() =>
    themeComponent(props.theme, 'Views', 'ServiceShow'),
);
const description = computed(
    () =>
        props.service.description ??
        `Persyaratan, prosedur, jangka waktu, dan biaya layanan ${props.service.name}.`,
);
</script>

<template>
    <Head :title="service.name">
        <meta name="description" :content="description" />
        <meta property="og:title" :content="service.name" />
        <meta property="og:description" :content="description" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" :content="site.name" />
    </Head>

    <component
        :is="layout"
        :site="site"
        :page-title="service.name"
        :current-path="inertiaPage.url"
    >
        <component
            :is="view"
            v-if="view"
            :service="service"
            :index-url="index_url"
            :site="site"
        />
        <!-- Cadangan bila tema tidak menyediakan Views/ServiceShow.vue. -->
        <article v-else class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-2xl font-bold">{{ service.name }}</h1>
            <p>{{ service.processing_time }} · {{ service.cost }}</p>
        </article>
    </component>
</template>
