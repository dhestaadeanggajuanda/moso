<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import MenuController from '@/actions/App/Http/Controllers/Admin/MenuController';
import MenuTree from '@/components/admin/MenuTree.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';
import type { MenuNode, Option } from '@/types/admin';

defineProps<{
    location: string;
    locations: Option[];
    tree: MenuNode[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Menu', href: MenuController.index() }],
    },
});
</script>

<template>
    <Head title="Menu" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Menu Builder"
            description="Susun navigasi situs hingga 3 tingkat. Item nonaktif beserta sub-menunya tidak tampil di situs."
        >
            <template #actions>
                <Button as-child>
                    <Link
                        :href="MenuController.create({ query: { location } })"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah item
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <nav aria-label="Lokasi menu" class="flex flex-wrap gap-2">
            <Button
                v-for="option in locations"
                :key="option.value"
                :variant="option.value === location ? 'default' : 'outline'"
                size="sm"
                as-child
            >
                <Link
                    :href="
                        MenuController.index({
                            query: { location: option.value },
                        })
                    "
                    :aria-current="
                        option.value === location ? 'page' : undefined
                    "
                    preserve-scroll
                >
                    {{ option.label }}
                </Link>
            </Button>
        </nav>

        <div
            v-if="tree.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada item di menu ini.
        </div>

        <MenuTree v-else :nodes="tree" />
    </div>
</template>
