<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    articles_count: number;
};

defineProps<{
    categories: CategoryRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kategori', href: CategoryController.index() }],
    },
});
</script>

<template>
    <Head title="Kategori" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Kategori"
            description="Taksonomi untuk mengelompokkan artikel."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="CategoryController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah kategori
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div
            v-if="categories.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada kategori.
        </div>

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="category in categories"
                :key="category.id"
                class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <p class="font-medium">{{ category.name }}</p>
                    <p class="truncate text-sm text-muted-foreground">
                        /{{ category.slug }} ·
                        {{ category.articles_count }} artikel
                    </p>
                </div>
                <div class="flex gap-1">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="CategoryController.edit(category.id)"
                            >Ubah
                            <span class="sr-only">{{
                                category.name
                            }}</span></Link
                        >
                    </Button>
                    <ConfirmDelete
                        :action="CategoryController.destroy(category.id)"
                        :item-name="category.name"
                    />
                </div>
            </li>
        </ul>
    </div>
</template>
