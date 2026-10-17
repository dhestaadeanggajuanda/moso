<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import ServiceController from '@/actions/App/Http/Controllers/Admin/ServiceController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PublishedBadge from '@/components/admin/PublishedBadge.vue';
import { Button } from '@/components/ui/button';

type ServiceRow = {
    id: number;
    name: string;
    slug: string;
    processing_time: string;
    cost: string;
    is_published: boolean;
};

defineProps<{
    services: ServiceRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Layanan', href: ServiceController.index() }],
    },
});
</script>

<template>
    <Head title="Layanan" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Layanan publik"
            description="Standar Pelayanan: persyaratan, prosedur, jangka waktu, biaya, produk, dan saluran pengaduan."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="ServiceController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah layanan
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div
            v-if="services.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada layanan.
        </div>

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="service in services"
                :key="service.id"
                class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ service.name }}</p>
                        <PublishedBadge :published="service.is_published" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ service.processing_time }} · {{ service.cost }}
                    </p>
                </div>
                <div class="flex gap-1">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="ServiceController.edit(service.id)"
                            >Ubah
                            <span class="sr-only">{{
                                service.name
                            }}</span></Link
                        >
                    </Button>
                    <ConfirmDelete
                        :action="ServiceController.destroy(service.id)"
                        :item-name="service.name"
                    />
                </div>
            </li>
        </ul>
    </div>
</template>
