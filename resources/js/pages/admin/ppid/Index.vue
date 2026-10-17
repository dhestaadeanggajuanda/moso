<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive } from 'vue';
import PublicDocumentController from '@/actions/App/Http/Controllers/Admin/PublicDocumentController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import PublishedBadge from '@/components/admin/PublishedBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { formatBytes } from '@/lib/format';
import type { Option, Paginated, StatusValue } from '@/types/admin';

type DocumentRow = {
    id: number;
    title: string;
    category: StatusValue;
    year: number | null;
    file_size: number;
    is_published: boolean;
};

const props = defineProps<{
    documents: Paginated<DocumentRow>;
    filters: { category?: string; year?: string };
    categories: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dokumen PPID', href: PublicDocumentController.index() },
        ],
    },
});

const filter = reactive({
    category: props.filters.category ?? '',
    year: props.filters.year ?? '',
});

function applyFilters(): void {
    router.visit(
        PublicDocumentController.index({
            query: {
                category: filter.category || undefined,
                year: filter.year || undefined,
            },
        }),
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Dokumen PPID" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Dokumen PPID"
            description="Daftar Informasi Publik: berkala, serta merta, dan setiap saat (UU 14/2008)."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="PublicDocumentController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Unggah dokumen
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form
            role="search"
            class="grid gap-3 sm:grid-cols-[1fr_9rem_auto] sm:items-end"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-1.5">
                <Label for="category">Kategori</Label>
                <NativeSelect id="category" v-model="filter.category">
                    <option value="">Semua kategori</option>
                    <option
                        v-for="category in categories"
                        :key="category.value"
                        :value="category.value"
                    >
                        {{ category.label }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="year">Tahun</Label>
                <Input
                    id="year"
                    v-model="filter.year"
                    type="number"
                    inputmode="numeric"
                    min="2000"
                    max="2100"
                    placeholder="mis. 2026"
                />
            </div>
            <Button type="submit" variant="secondary">Terapkan</Button>
        </form>

        <div
            v-if="documents.data.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada dokumen yang cocok.
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
                            <p class="font-medium">{{ document.title }}</p>
                            <PublishedBadge
                                :published="document.is_published"
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ document.category.label }}
                            <template v-if="document.year">
                                · {{ document.year }}</template
                            >
                            · PDF {{ formatBytes(document.file_size) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Button variant="ghost" size="sm" as-child>
                            <a
                                :href="
                                    PublicDocumentController.file.url(
                                        document.id,
                                    )
                                "
                                target="_blank"
                                rel="noopener"
                                >Lihat
                                <span class="sr-only">{{
                                    document.title
                                }}</span></a
                            >
                        </Button>
                        <Button variant="ghost" size="sm" as-child>
                            <Link
                                :href="
                                    PublicDocumentController.edit(document.id)
                                "
                                >Ubah
                                <span class="sr-only">{{
                                    document.title
                                }}</span></Link
                            >
                        </Button>
                        <ConfirmDelete
                            :action="
                                PublicDocumentController.destroy(document.id)
                            "
                            :item-name="document.title"
                        />
                    </div>
                </li>
            </ul>

            <Pagination :paginator="documents" />
        </template>
    </div>
</template>
