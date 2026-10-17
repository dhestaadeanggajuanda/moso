<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import AgendaController from '@/actions/App/Http/Controllers/Admin/AgendaController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type AgendaRow = {
    id: number;
    title: string;
    location: string | null;
    start_at: string;
    end_at: string | null;
    is_published: boolean;
};

defineProps<{
    agendas: Paginated<AgendaRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agenda', href: AgendaController.index() }],
    },
});
</script>

<template>
    <Head title="Agenda" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Agenda kegiatan"
            description="Jadwal kegiatan yang tampil di situs publik."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="AgendaController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah agenda
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div
            v-if="agendas.data.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada agenda.
        </div>

        <template v-else>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="agenda in agendas.data"
                    :key="agenda.id"
                    class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ agenda.title }}</p>
                            <Badge
                                :variant="
                                    agenda.is_published ? 'default' : 'outline'
                                "
                            >
                                {{ agenda.is_published ? 'Terbit' : 'Draf' }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDateTime(agenda.start_at) }}
                            <template v-if="agenda.location">
                                · {{ agenda.location }}</template
                            >
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="AgendaController.edit(agenda.id)"
                                >Ubah
                                <span class="sr-only">{{
                                    agenda.title
                                }}</span></Link
                            >
                        </Button>
                        <ConfirmDelete
                            :action="AgendaController.destroy(agenda.id)"
                            :item-name="agenda.title"
                        />
                    </div>
                </li>
            </ul>

            <Pagination :paginator="agendas" />
        </template>
    </div>
</template>
