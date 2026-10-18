<script setup lang="ts">
import { computed, ref } from 'vue';
import { useBlockImageUpload } from '@/Builder/useBlockImageUpload';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useBuilderStore } from '@/stores/builder';
import type { PageBlock } from '@/types/blocks';

const props = defineProps<{
    errors: Record<string, string>;
    pageId: number;
    imageBaseUrl: string;
}>();

const store = useBuilderStore();
const block = computed(() => store.selected as PageBlock<'HeroBanner'>);

const fileInput = ref<HTMLInputElement | null>(null);
const { http: upload, upload: uploadImage } = useBlockImageUpload(
    () => props.pageId,
);

const previewUrl = computed(() =>
    block.value.props.image_path
        ? `${props.imageBaseUrl}/${block.value.props.image_path}`
        : null,
);

const imageError = computed(
    () => upload.errors.image ?? props.errors.image_path,
);

function chooseFile(): void {
    fileInput.value?.click();
}

function onFileChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    uploadImage(file, (path) => {
        block.value.props.image_path = path;
    });

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function removeImage(): void {
    block.value.props.image_path = null;
    block.value.props.image_alt = null;
}
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

        <fieldset class="grid gap-2">
            <legend class="mb-1.5 text-sm font-medium">Foto latar</legend>
            <img
                v-if="previewUrl"
                :src="previewUrl"
                :alt="block.props.image_alt ?? 'Pratinjau foto banner'"
                class="aspect-video w-full rounded-md border object-cover"
            />
            <p v-else class="text-sm text-muted-foreground">
                Tanpa foto, banner memakai warna utama tema.
            </p>

            <input
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                tabindex="-1"
                aria-hidden="true"
                @change="onFileChange"
            />
            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="upload.processing"
                    @click="chooseFile"
                >
                    {{
                        upload.processing
                            ? `Mengunggah… ${upload.progress?.percentage ?? 0}%`
                            : previewUrl
                              ? 'Ganti foto'
                              : 'Unggah foto'
                    }}
                </Button>
                <Button
                    v-if="previewUrl"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeImage"
                >
                    Hapus foto
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">
                JPG, PNG, atau WebP, maks. 5 MB. Foto disimpan setelah susunan
                halaman disimpan.
            </p>
            <InputError :message="imageError" />
        </fieldset>

        <div v-if="previewUrl" class="grid gap-1.5">
            <Label for="hero-image-alt">Teks alternatif foto</Label>
            <Input
                id="hero-image-alt"
                v-model="block.props.image_alt"
                maxlength="160"
                placeholder="mis. Petugas melayani warga di loket"
                aria-describedby="hero-image-alt-help"
            />
            <p id="hero-image-alt-help" class="text-xs text-muted-foreground">
                Kosongkan bila foto hanya hiasan.
            </p>
            <InputError :message="errors.image_alt" />
        </div>

        <label class="flex min-h-6 items-center gap-2 text-sm">
            <input
                v-model="block.props.show_search"
                type="checkbox"
                class="size-4 accent-primary"
            />
            Tampilkan kolom cari layanan
        </label>

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
