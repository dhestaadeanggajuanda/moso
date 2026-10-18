<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useBuilderStore } from '@/stores/builder';
import type { AksesCepatIcon, PageBlock } from '@/types/blocks';

defineProps<{
    errors: Record<string, string>;
}>();

const MAX_ITEMS = 8;

const iconOptions: { value: AksesCepatIcon; label: string }[] = [
    { value: 'layanan', label: 'Layanan' },
    { value: 'pengaduan', label: 'Pengaduan' },
    { value: 'dokumen', label: 'Dokumen (PPID)' },
    { value: 'laporan', label: 'Laporan kinerja (E-SAKIP)' },
    { value: 'kontak', label: 'Kontak' },
    { value: 'berita', label: 'Berita' },
    { value: 'agenda', label: 'Agenda' },
    { value: 'lokasi', label: 'Lokasi' },
];

const store = useBuilderStore();
const block = computed(() => store.selected as PageBlock<'AksesCepat'>);

function addItem(): void {
    block.value.props.items.push({ label: '', url: '', icon: 'dokumen' });
}

function removeItem(index: number): void {
    block.value.props.items.splice(index, 1);
}
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-1.5">
            <Label for="akses-title">Judul blok (opsional)</Label>
            <Input
                id="akses-title"
                v-model="block.props.title"
                maxlength="120"
                placeholder="Kosongkan untuk deret tanpa judul"
            />
            <InputError :message="errors.title" />
        </div>

        <fieldset
            v-for="(item, index) in block.props.items"
            :key="index"
            class="grid gap-2 rounded-md border p-3"
        >
            <legend class="px-1 text-sm font-medium">
                Tautan {{ index + 1 }}
            </legend>
            <div class="grid gap-1.5">
                <Label :for="`akses-label-${index}`">Teks</Label>
                <Input
                    :id="`akses-label-${index}`"
                    v-model="item.label"
                    maxlength="40"
                    placeholder="mis. PPID"
                    :aria-invalid="!!errors[`items.${index}.label`]"
                />
                <InputError :message="errors[`items.${index}.label`]" />
            </div>
            <div class="grid gap-1.5">
                <Label :for="`akses-url-${index}`">Alamat</Label>
                <Input
                    :id="`akses-url-${index}`"
                    v-model.trim="item.url"
                    maxlength="500"
                    placeholder="/ppid atau https://..."
                    :aria-invalid="!!errors[`items.${index}.url`]"
                />
                <InputError :message="errors[`items.${index}.url`]" />
            </div>
            <div class="grid gap-1.5">
                <Label :for="`akses-icon-${index}`">Ikon</Label>
                <NativeSelect :id="`akses-icon-${index}`" v-model="item.icon">
                    <option
                        v-for="option in iconOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors[`items.${index}.icon`]" />
            </div>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="justify-self-start"
                :disabled="block.props.items.length <= 1"
                @click="removeItem(index)"
            >
                Hapus tautan {{ index + 1 }}
            </Button>
        </fieldset>

        <InputError :message="errors.items" />

        <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="block.props.items.length >= MAX_ITEMS"
            @click="addItem"
        >
            Tambah tautan
        </Button>
    </div>
</template>
