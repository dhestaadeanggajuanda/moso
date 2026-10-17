<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import type { Paginated } from '@/types/admin';

const props = defineProps<{
    paginator: Paginated<unknown>;
}>();

// Link pertama/terakhir dari Laravel adalah "Previous"/"Next"; nomor halaman ada di tengah.
const pages = computed(() => props.paginator.links.slice(1, -1));
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        aria-label="Navigasi halaman"
        class="flex flex-col items-center justify-between gap-3 sm:flex-row"
    >
        <p class="text-sm text-muted-foreground">
            Menampilkan {{ paginator.from }}–{{ paginator.to }} dari
            {{ paginator.total }}
        </p>
        <ul class="flex flex-wrap items-center gap-1">
            <li>
                <Link
                    v-if="paginator.prev_page_url"
                    :href="paginator.prev_page_url"
                    preserve-scroll
                    class="inline-flex size-9 items-center justify-center rounded-md border hover:bg-accent"
                >
                    <ChevronLeft class="size-4" aria-hidden="true" />
                    <span class="sr-only">Sebelumnya</span>
                </Link>
            </li>
            <li v-for="link in pages" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    :class="[
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-2 text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-accent',
                    ]"
                >
                    {{ link.label }}
                </Link>
                <span v-else class="px-2 text-sm text-muted-foreground">{{
                    link.label
                }}</span>
            </li>
            <li>
                <Link
                    v-if="paginator.next_page_url"
                    :href="paginator.next_page_url"
                    preserve-scroll
                    class="inline-flex size-9 items-center justify-center rounded-md border hover:bg-accent"
                >
                    <ChevronRight class="size-4" aria-hidden="true" />
                    <span class="sr-only">Berikutnya</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
