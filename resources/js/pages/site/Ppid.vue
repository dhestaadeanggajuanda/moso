<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { themeComponent } from '@/Core/themes';
import type {
    Paginated,
    PpidCategoryTab,
    PpidCategoryValue,
    PublicDocumentItem,
    SiteContext,
} from '@/types/site';

const props = defineProps<{
    documents: Paginated<PublicDocumentItem>;
    categories: PpidCategoryTab[];
    category: PpidCategoryValue | null;
    total: number;
    theme: string;
    site: SiteContext;
}>();

const inertiaPage = usePage();

const layout = computed(
    () => themeComponent(props.theme, 'Layouts', 'SiteLayout') ?? 'div',
);
const view = computed(() => themeComponent(props.theme, 'Views', 'PpidIndex'));
const activeLabel = computed(
    () => props.categories.find((item) => item.value === props.category)?.label,
);
const title = computed(() =>
    activeLabel.value
        ? `${activeLabel.value} | Informasi Publik (PPID)`
        : 'Informasi Publik (PPID)',
);
const description =
    'Daftar informasi publik dinas: informasi berkala, serta merta, dan setiap saat.';
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
            :documents="documents"
            :categories="categories"
            :category="category"
            :total="total"
            :site="site"
        />
        <!-- Cadangan bila tema tidak menyediakan Views/PpidIndex.vue. -->
        <section v-else class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-2xl font-bold">Informasi Publik (PPID)</h1>
            <ul class="mt-4 list-disc pl-5">
                <li v-for="document in documents.data" :key="document.slug">
                    <a :href="document.download_url" class="underline">{{
                        document.title
                    }}</a>
                </li>
            </ul>
        </section>
    </component>
</template>
