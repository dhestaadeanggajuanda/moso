<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import SakipController from '@/actions/App/Http/Controllers/Admin/SakipController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import PublishedBadge from '@/components/admin/PublishedBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatBytes } from '@/lib/format';
import type { Paginated, StatusValue } from '@/types/admin';

type SakipRow = {
    id: number;
    year: number;
    document_title: string;
    document_type: StatusValue;
    file_size: number;
    is_published: boolean;
};

const props = defineProps<{
    documents: Paginated<SakipRow>;
    filters: { year?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'E-SAKIP', href: SakipController.index() }],
    },
});

const year = ref(props.filters.year ?? '');

function applyFilters(): void {
    router.visit(
        SakipController.index({ query: { year: year.value || undefined } }),
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="E-SAKIP" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="E-SAKIP"
            description="Dokumen akuntabilitas kinerja: Renstra, Renja, IKU, Perjanjian Kinerja, Rencana Aksi, dan LKjIP."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="SakipController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Unggah dokumen
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form
            role="search"
            class="flex flex-wrap items-end gap-3"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-1.5">
                <Label for="year">Tahun</Label>
                <Input
                    id="year"
                    v-model="year"
                    type="number"
                    inputmode="numeric"
                    min="2000"
                    max="2100"
                    placeholder="Semua tahun"
                    class="w-36"
                />
            </div>
            <Button type="submit" variant="secondary">Terapkan</Button>
        </form>

        <div
            v-if="documents.data.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada dokumen SAKIP.
        </div>

        <template v-else>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="document in documents.data"
                    :key="document.id"
                    class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">
                                {{ document.document_title }}
                            </p>
                            <PublishedBadge
                                :published="document.is_published"
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ document.year }} ·
                            {{ document.document_type.label }} · PDF
                            {{ formatBytes(document.file_size) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Button variant="ghost" size="sm" as-child>
                            <a
                                :href="SakipController.file.url(document.id)"
                                target="_blank"
                                rel="noopener"
                                >Lihat
                                <span class="sr-only">{{
                                    document.document_title
                                }}</span></a
                            >
                        </Button>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="SakipController.edit(document.id)"
                                >Ubah
                                <span class="sr-only">{{
                                    document.document_title
                                }}</span></Link
                            >
                        </Button>
                        <ConfirmDelete
                            :action="SakipController.destroy(document.id)"
                            :item-name="document.document_title"
                        />
                    </div>
                </li>
            </ul>

            <Pagination :paginator="documents" />
        </template>
    </div>
</template>
