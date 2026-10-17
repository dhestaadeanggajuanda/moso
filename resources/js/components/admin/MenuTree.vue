<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import MenuController from '@/actions/App/Http/Controllers/Admin/MenuController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { MenuNode } from '@/types/admin';

withDefaults(
    defineProps<{
        nodes: MenuNode[];
        level?: number;
    }>(),
    { level: 1 },
);
</script>

<template>
    <ul :class="level > 1 ? 'mt-2 ml-4 border-l pl-3 sm:ml-6' : 'grid gap-2'">
        <li v-for="node in nodes" :key="node.id">
            <div
                class="flex flex-col gap-2 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0 space-y-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ node.label }}</p>
                        <Badge v-if="!node.is_active" variant="outline"
                            >Nonaktif</Badge
                        >
                        <ExternalLink
                            v-if="node.open_in_new_tab"
                            class="size-3.5 text-muted-foreground"
                            aria-label="Dibuka di tab baru"
                        />
                    </div>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ node.href ?? 'Tujuan tidak tersedia' }} · urutan
                        {{ node.sort_order }}
                    </p>
                </div>
                <div class="flex gap-1">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="MenuController.edit(node.id)"
                            >Ubah
                            <span class="sr-only">{{ node.label }}</span></Link
                        >
                    </Button>
                    <ConfirmDelete
                        :action="MenuController.destroy(node.id)"
                        :item-name="node.label"
                    />
                </div>
            </div>
            <MenuTree
                v-if="node.children.length"
                :nodes="node.children"
                :level="level + 1"
            />
        </li>
    </ul>
</template>
