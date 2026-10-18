<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBuilderStore } from '@/stores/builder';
import type { PageBlock } from '@/types/blocks';

defineProps<{
    errors: Record<string, string>;
}>();

const store = useBuilderStore();
const block = computed(() => store.selected as PageBlock<'LayananList'>);
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-1.5">
            <Label for="layanan-title">Judul blok</Label>
            <Input
                id="layanan-title"
                v-model="block.props.title"
                maxlength="120"
            />
            <InputError :message="errors.title" />
        </div>
        <div class="grid gap-1.5">
            <Label for="layanan-limit">Jumlah layanan</Label>
            <Input
                id="layanan-limit"
                v-model.number="block.props.limit"
                type="number"
                inputmode="numeric"
                min="1"
                max="24"
            />
            <InputError :message="errors.limit" />
        </div>
        <label class="flex min-h-6 items-center gap-2 text-sm">
            <input
                v-model="block.props.show_search"
                type="checkbox"
                class="size-4 accent-primary"
            />
            Tampilkan kolom cari layanan
        </label>
        <p class="text-xs text-muted-foreground">
            Layanan diambil dari menu Standar Pelayanan (hanya yang terbit),
            urut sesuai urutan tampil.
        </p>
    </div>
</template>
