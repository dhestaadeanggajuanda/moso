<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useBuilderStore } from '@/stores/builder';
import type { Option } from '@/types/admin';
import type { PageBlock } from '@/types/blocks';

defineProps<{
    errors: Record<string, string>;
    categories: Option[];
    modes: Option[];
}>();

const store = useBuilderStore();
// Dipakai bersama oleh NewsGridHybrid dan NewsCarousel (props sama).
const block = computed(
    () => store.selected as PageBlock<'NewsGridHybrid' | 'NewsCarousel'>,
);
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-1.5">
            <Label for="ngh-title">Judul blok</Label>
            <Input id="ngh-title" v-model="block.props.title" maxlength="120" />
            <InputError :message="errors.title" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="grid gap-1.5">
                <Label for="ngh-limit">Jumlah berita</Label>
                <Input
                    id="ngh-limit"
                    v-model.number="block.props.limit"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="12"
                />
                <InputError :message="errors.limit" />
            </div>
            <div class="grid gap-1.5">
                <Label for="ngh-columns">{{
                    block.type === 'NewsCarousel'
                        ? 'Kartu per layar (desktop)'
                        : 'Kolom (desktop)'
                }}</Label>
                <NativeSelect
                    id="ngh-columns"
                    v-model.number="block.props.columns"
                >
                    <option :value="2">2</option>
                    <option :value="3">3</option>
                    <option :value="4">4</option>
                </NativeSelect>
                <InputError :message="errors.columns" />
            </div>
        </div>

        <div class="grid gap-1.5">
            <Label for="ngh-category">Kategori berita lokal</Label>
            <NativeSelect
                id="ngh-category"
                v-model="block.props.local_category"
            >
                <option :value="null">Semua kategori</option>
                <option
                    v-for="category in categories"
                    :key="category.value"
                    :value="category.value"
                >
                    {{ category.label }}
                </option>
            </NativeSelect>
            <InputError :message="errors.local_category" />
        </div>

        <fieldset class="grid gap-3 rounded-md border p-3">
            <legend class="px-1 text-sm font-medium">Media Center Kota</legend>

            <label class="flex min-h-6 items-center gap-2 text-sm">
                <input
                    v-model="block.props.fetch_media_center"
                    type="checkbox"
                    class="size-4 accent-primary"
                />
                Gabungkan berita dari Media Center
            </label>

            <template v-if="block.props.fetch_media_center">
                <div class="grid gap-1.5">
                    <Label for="ngh-keyword">Kata kunci</Label>
                    <Input
                        id="ngh-keyword"
                        v-model.trim="block.props.media_center_keyword"
                        placeholder="mis. sosial"
                        maxlength="50"
                        autocomplete="off"
                        aria-describedby="ngh-keyword-help"
                        :aria-invalid="!!errors.media_center_keyword"
                    />
                    <p
                        id="ngh-keyword-help"
                        class="text-xs text-muted-foreground"
                    >
                        Huruf kecil, angka, dan tanda hubung. Untuk mode tag
                        atau kategori, isi slug-nya di Media Center.
                    </p>
                    <InputError :message="errors.media_center_keyword" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="ngh-mode">Cari berdasarkan</Label>
                    <NativeSelect
                        id="ngh-mode"
                        v-model="block.props.media_center_mode"
                    >
                        <option
                            v-for="mode in modes"
                            :key="mode.value"
                            :value="mode.value"
                        >
                            {{ mode.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.media_center_mode" />
                </div>
            </template>
        </fieldset>
    </div>
</template>
