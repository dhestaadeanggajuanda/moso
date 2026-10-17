<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useBuilderStore } from '@/stores/builder';
import type { PageBlock } from '@/types/blocks';

defineProps<{
    errors: Record<string, string>;
}>();

const store = useBuilderStore();
const block = computed(() => store.selected as PageBlock<'RichText'>);
</script>

<template>
    <div class="grid gap-1.5">
        <Label for="richtext-html">Isi teks</Label>
        <Textarea
            id="richtext-html"
            v-model="block.props.html"
            rows="12"
            class="font-mono text-sm"
            aria-describedby="richtext-help"
        />
        <p id="richtext-help" class="text-xs text-muted-foreground">
            HTML dasar (paragraf, judul, daftar, tautan). Script dan atribut
            berbahaya dibuang otomatis saat disimpan.
        </p>
        <InputError :message="errors.html" />
    </div>
</template>
