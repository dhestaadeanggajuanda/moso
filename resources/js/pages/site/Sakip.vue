<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { themeComponent } from '@/Core/themes';
import type { SakipYearGroup, SiteContext } from '@/types/site';

const props = defineProps<{
    groups: SakipYearGroup[];
    total: number;
    filters: { year: number | null; type: string | null };
    yearOptions: number[];
    typeOptions: { value: string; label: string }[];
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const view = computed(() => themeComponent(props.theme, 'Views', 'SakipIndex'));
const title = computed(() =>
    props.filters.year ? `E-SAKIP Tahun ${props.filters.year}` : 'E-SAKIP',
);
const description =
    'Dokumen akuntabilitas kinerja dinas: Renstra, Renja, IKU, Perjanjian Kinerja, Rencana Aksi, dan LKjIP.';
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" :content="site.name" />
        <meta v-if="filters.type" name="robots" content="noindex" />
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
            :groups="groups"
            :total="total"
            :filters="filters"
            :year-options="yearOptions"
            :type-options="typeOptions"
            :site="site"
        />
        <!-- Cadangan bila tema tidak menyediakan Views/SakipIndex.vue. -->
        <section v-else class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-2xl font-bold">E-SAKIP</h1>
            <template v-for="group in groups" :key="group.year">
                <h2 class="mt-6 text-xl font-bold">Tahun {{ group.year }}</h2>
                <ul class="mt-2 list-disc pl-5">
                    <li v-for="document in group.documents" :key="document.id">
                        <a :href="document.download_url" class="underline">{{
                            document.title
                        }}</a>
                    </li>
                </ul>
            </template>
        </section>
    </component>
</template>
