<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive } from 'vue';
import ArticleController from '@/actions/App/Http/Controllers/Admin/ArticleController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDateTime } from '@/lib/format';
import type { Option, Paginated, StatusValue } from '@/types/admin';

type ArticleRow = {
    id: number;
    title: string;
    status: StatusValue;
    author: string;
    categories: string[];
    published_at: string | null;
    updated_at: string | null;
    can: { update: boolean; delete: boolean };
};

const props = defineProps<{
    articles: Paginated<ArticleRow>;
    filters: { search?: string; status?: string };
    statuses: Option[];
    can: { create: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Artikel', href: ArticleController.index() }],
    },
});

const filter = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

function applyFilters(): void {
    router.visit(
        ArticleController.index({
            query: {
                search: filter.search || undefined,
                status: filter.status || undefined,
            },
        }),
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Artikel" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Artikel"
            description="Kelola berita dan artikel dinas."
        >
            <template #actions>
                <Button v-if="can.create" as-child>
                    <Link :href="ArticleController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Tulis artikel
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form
            role="search"
            class="grid gap-3 sm:grid-cols-[1fr_12rem_auto] sm:items-end"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-1.5">
                <Label for="search">Cari judul</Label>
                <Input
                    id="search"
                    v-model="filter.search"
                    type="search"
                    placeholder="Ketik judul artikel"
                />
            </div>
            <div class="grid gap-1.5">
                <Label for="status">Status</Label>
                <NativeSelect id="status" v-model="filter.status">
                    <option value="">Semua status</option>
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </NativeSelect>
            </div>
            <Button type="submit" variant="secondary">Terapkan</Button>
        </form>

        <div
            v-if="articles.data.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada artikel yang cocok.
        </div>

        <template v-else>
            <!-- Ponsel: daftar kartu. Layar md ke atas: tabel. -->
            <ul class="grid gap-3 md:hidden">
                <li
                    v-for="article in articles.data"
                    :key="article.id"
                    class="space-y-2 rounded-lg border p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-medium">{{ article.title }}</p>
                        <StatusBadge :status="article.status" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ article.author }} ·
                        {{ formatDateTime(article.updated_at) }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            v-if="article.can.update"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <Link :href="ArticleController.edit(article.id)"
                                >Ubah</Link
                            >
                        </Button>
                        <ConfirmDelete
                            v-if="article.can.delete"
                            :action="ArticleController.destroy(article.id)"
                            :item-name="article.title"
                        />
                    </div>
                </li>
            </ul>

            <div class="hidden overflow-x-auto rounded-lg border md:block">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Daftar artikel
                    </caption>
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Judul
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Status
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Penulis
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Diperbarui
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="article in articles.data"
                            :key="article.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ article.title }}</p>
                                <p
                                    v-if="article.categories.length"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ article.categories.join(', ') }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="article.status" />
                            </td>
                            <td class="px-4 py-3">{{ article.author }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDateTime(article.updated_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="article.can.update"
                                        variant="ghost"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                ArticleController.edit(
                                                    article.id,
                                                )
                                            "
                                            >Ubah
                                            <span class="sr-only">{{
                                                article.title
                                            }}</span></Link
                                        >
                                    </Button>
                                    <ConfirmDelete
                                        v-if="article.can.delete"
                                        :action="
                                            ArticleController.destroy(
                                                article.id,
                                            )
                                        "
                                        :item-name="article.title"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="articles" />
        </template>
    </div>
</template>
