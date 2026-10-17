<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { RouteDefinition } from '@/wayfinder';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        action: RouteDefinition<'delete'>;
        itemName: string;
        triggerLabel?: string;
    }>(),
    { triggerLabel: 'Hapus' },
);

const open = ref(false);
const processing = ref(false);

function confirm(): void {
    router.visit(props.action, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => {
            processing.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="ghost" size="sm" class="text-destructive">
                {{ triggerLabel }}
                <span class="sr-only">{{ itemName }}</span>
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus "{{ itemName }}"?</DialogTitle>
                <DialogDescription>
                    Tindakan ini tidak dapat dibatalkan dari halaman ini.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Batal</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirm"
                >
                    Ya, hapus
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
