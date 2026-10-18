<script setup lang="ts">
/**
 * Riwayat alur persetujuan artikel: perubahan status beserta catatannya, dan
 * komentar diskusi. Semua teks dirender sebagai teks polos.
 */
import { ArrowRight, MessageSquare, RefreshCw } from '@lucide/vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime } from '@/lib/format';
import type { ArticleHistoryEntry } from '@/types/admin';

defineProps<{
    entries: ArticleHistoryEntry[];
}>();
</script>

<template>
    <div>
        <p
            v-if="entries.length === 0"
            class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
        >
            Belum ada riwayat. Riwayat terisi saat artikel diajukan, direview,
            atau dikomentari.
        </p>

        <ol v-else class="grid gap-4">
            <li
                v-for="entry in entries"
                :key="entry.id"
                class="grid grid-cols-[1.75rem_1fr] gap-3"
            >
                <span
                    class="mt-0.5 grid size-7 place-items-center rounded-full bg-muted text-muted-foreground"
                    aria-hidden="true"
                >
                    <RefreshCw
                        v-if="entry.type === 'status_change'"
                        class="size-3.5"
                    />
                    <MessageSquare v-else class="size-3.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm">
                        <span class="font-medium">{{ entry.user }}</span>
                        <template v-if="entry.type === 'status_change'">
                            mengubah status
                        </template>
                        <template v-else> berkomentar</template>
                        <span class="text-muted-foreground">
                            ·
                            <time :datetime="entry.created_at ?? undefined">
                                {{ formatDateTime(entry.created_at) }}
                            </time>
                        </span>
                    </p>
                    <p
                        v-if="entry.type === 'status_change' && entry.to"
                        class="mt-1 flex flex-wrap items-center gap-1.5"
                    >
                        <StatusBadge v-if="entry.from" :status="entry.from" />
                        <ArrowRight
                            v-if="entry.from"
                            class="size-3.5 text-muted-foreground"
                            aria-label="menjadi"
                        />
                        <StatusBadge :status="entry.to" />
                    </p>
                    <p
                        v-if="entry.body"
                        class="mt-2 rounded-md bg-muted/60 p-3 text-sm break-words whitespace-pre-line"
                    >
                        {{ entry.body }}
                    </p>
                </div>
            </li>
        </ol>
    </div>
</template>
