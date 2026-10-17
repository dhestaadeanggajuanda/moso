<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useBuilderStore } from '@/stores/builder';
import type { PageBlock } from '@/types/blocks';

defineProps<{
    errors: Record<string, string>;
}>();

const store = useBuilderStore();
const block = computed(() => store.selected as PageBlock<'HeroBanner'>);
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-1.5">
            <Label for="hero-heading">Judul</Label>
            <Input
                id="hero-heading"
                v-model="block.props.heading"
                maxlength="120"
                :aria-invalid="!!errors.heading"
            />
            <InputError :message="errors.heading" />
        </div>

        <div class="grid gap-1.5">
            <Label for="hero-subheading">Subjudul</Label>
            <Textarea
                id="hero-subheading"
                v-model="block.props.subheading"
                rows="2"
                maxlength="300"
            />
            <InputError :message="errors.subheading" />
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
            <div class="grid gap-1.5">
                <Label for="hero-cta-label">Teks tombol</Label>
                <Input
                    id="hero-cta-label"
                    v-model="block.props.cta_label"
                    maxlength="40"
                    placeholder="mis. Lihat layanan"
                />
                <InputError :message="errors.cta_label" />
            </div>
            <div class="grid gap-1.5">
                <Label for="hero-cta-url">Tautan tombol</Label>
                <Input
                    id="hero-cta-url"
                    v-model.trim="block.props.cta_url"
                    maxlength="500"
                    placeholder="/layanan"
                    :aria-invalid="!!errors.cta_url"
                />
                <InputError :message="errors.cta_url" />
            </div>
        </div>

        <div class="grid gap-1.5">
            <Label for="hero-variant">Gaya</Label>
            <NativeSelect id="hero-variant" v-model="block.props.variant">
                <option value="primary">Warna utama</option>
                <option value="muted">Netral</option>
            </NativeSelect>
            <InputError :message="errors.variant" />
        </div>
    </div>
</template>
