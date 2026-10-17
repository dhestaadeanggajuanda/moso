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
const block = computed(() => store.selected as PageBlock<'AgendaKegiatan'>);
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-1.5">
            <Label for="agenda-title">Judul blok</Label>
            <Input
                id="agenda-title"
                v-model="block.props.title"
                maxlength="120"
            />
            <InputError :message="errors.title" />
        </div>
        <div class="grid gap-1.5">
            <Label for="agenda-limit">Jumlah agenda</Label>
            <Input
                id="agenda-limit"
                v-model.number="block.props.limit"
                type="number"
                inputmode="numeric"
                min="1"
                max="12"
            />
            <InputError :message="errors.limit" />
        </div>
    </div>
</template>
