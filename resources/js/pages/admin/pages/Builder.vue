<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import BuilderEditor from '@/Builder/BuilderEditor.vue';
import { Button } from '@/components/ui/button';
import type { Option, StatusValue } from '@/types/admin';
import type { AnyPageBlock, BlockCatalogEntry } from '@/types/blocks';

defineProps<{
    page: {
        id: number;
        title: string;
        slug: string;
        status: StatusValue;
        updated_at: string | null;
    };
    blocks: AnyPageBlock[];
    catalog: BlockCatalogEntry[];
    categories: Option[];
    mediaCenterModes: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Halaman', href: PageController.index() }],
    },
});
</script>

<template>
    <Head :title="`Page Builder: ${page.title}`" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <PageHeader
            :title="`Page Builder: ${page.title}`"
            :description="`/${page.slug}`"
        >
            <template #actions>
                <StatusBadge :status="page.status" />
                <Button variant="outline" size="sm" as-child>
                    <Link :href="PageController.edit(page.id)"
                        >Pengaturan halaman</Link
                    >
                </Button>
            </template>
        </PageHeader>

        <BuilderEditor
            :page-id="page.id"
            :version="page.updated_at"
            :blocks="blocks"
            :catalog="catalog"
            :categories="categories"
            :media-center-modes="mediaCenterModes"
        />
    </div>
</template>
