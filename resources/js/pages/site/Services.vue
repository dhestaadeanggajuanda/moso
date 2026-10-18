<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { themeComponent } from '@/Core/themes';
import type { ServiceSummary } from '@/types/blocks';
import type { Paginated, SiteContext } from '@/types/site';

const props = defineProps<{
    services: Paginated<ServiceSummary>;
    search: string;
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const view = computed(() =>
    themeComponent(props.theme, 'Views', 'ServiceIndex'),
);
const title = computed(() =>
    props.search ? `Cari layanan: ${props.search}` : 'Layanan Publik',
);
const description = 'Persyaratan, prosedur, jangka waktu, dan biaya layanan.';
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" :content="site.name" />
        <meta v-if="search" name="robots" content="noindex" />
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
            :services="services"
            :search="search"
            :site="site"
        />
        <!-- Cadangan bila tema tidak menyediakan Views/ServiceIndex.vue. -->
        <section v-else class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-2xl font-bold">Layanan Publik</h1>
            <ul class="mt-4 list-disc pl-5">
                <li v-for="service in services.data" :key="service.id">
                    <a :href="service.url" class="underline">{{
                        service.name
                    }}</a>
                </li>
            </ul>
        </section>
    </component>
</template>
