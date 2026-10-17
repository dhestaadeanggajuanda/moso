<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import PageBuilderController from '@/actions/App/Http/Controllers/Admin/PageBuilderController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import type { StatusValue } from '@/types/admin';

type PageRow = {
    id: number;
    title: string;
    slug: string;
    status: StatusValue;
    blocks_count: number;
    updated_at: string | null;
};

defineProps<{
    pages: PageRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Halaman', href: PageController.index() }],
    },
});
</script>

<template>
    <Head title="Halaman" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Halaman"
            description="Halaman statis dan landing page. Isi blok disusun lewat Page Builder (Fase 3)."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="PageController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Buat halaman
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div
            v-if="pages.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada halaman.
        </div>

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="page in pages"
                :key="page.id"
                class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ page.title }}</p>
                        <StatusBadge :status="page.status" />
                    </div>
                    <p class="truncate text-sm text-muted-foreground">
                        /{{ page.slug }} · {{ page.blocks_count }} blok ·
                        {{ formatDateTime(page.updated_at) }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-1">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="PageBuilderController.edit(page.id)"
                            >Susun blok
                            <span class="sr-only">{{ page.title }}</span></Link
                        >
                    </Button>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="PageController.edit(page.id)"
                            >Ubah
                            <span class="sr-only">{{ page.title }}</span></Link
                        >
                    </Button>
                    <ConfirmDelete
                        :action="PageController.destroy(page.id)"
                        :item-name="page.title"
                    />
                </div>
            </li>
        </ul>
    </div>
</template>
