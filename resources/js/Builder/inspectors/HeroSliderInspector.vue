<script setup lang="ts">
import { computed, ref } from 'vue';
import { useBlockImageUpload } from '@/Builder/useBlockImageUpload';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useBuilderStore } from '@/stores/builder';
import type { HeroSlide, PageBlock } from '@/types/blocks';

const props = defineProps<{
    errors: Record<string, string>;
    pageId: number;
    imageBaseUrl: string;
}>();

const MAX_SLIDES = 5;

const store = useBuilderStore();
const block = computed(() => store.selected as PageBlock<'HeroSlider'>);

const { http: upload, upload: uploadImage } = useBlockImageUpload(
    () => props.pageId,
);
const uploadingIndex = ref<number | null>(null);

function previewUrl(slide: HeroSlide): string | null {
    return slide.image_path
        ? `${props.imageBaseUrl}/${slide.image_path}`
        : null;
}

function onFileChange(event: Event, index: number): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploadingIndex.value = index;
    uploadImage(file, (path) => {
        const slide = block.value.props.slides[index];

        if (slide) {
            slide.image_path = path;
        }
    });
    input.value = '';
}

function addSlide(): void {
    block.value.props.slides.push({
        heading: '',
        subheading: null,
        cta_label: null,
        cta_url: null,
        image_path: null,
        image_alt: null,
    });
}

function removeSlide(index: number): void {
    block.value.props.slides.splice(index, 1);
}

function moveSlide(index: number, direction: -1 | 1): void {
    const slides = block.value.props.slides;
    const target = index + direction;

    if (target < 0 || target >= slides.length) {
        return;
    }

    [slides[index], slides[target]] = [slides[target], slides[index]];
}
</script>

<template>
    <div class="grid gap-4">
        <fieldset
            v-for="(slide, index) in block.props.slides"
            :key="index"
            class="grid gap-2 rounded-md border p-3"
        >
            <legend class="px-1 text-sm font-medium">
                Slide {{ index + 1 }}
            </legend>

            <div class="grid gap-1.5">
                <Label :for="`slide-heading-${index}`">Judul</Label>
                <Input
                    :id="`slide-heading-${index}`"
                    v-model="slide.heading"
                    maxlength="120"
                    :aria-invalid="!!errors[`slides.${index}.heading`]"
                />
                <InputError :message="errors[`slides.${index}.heading`]" />
            </div>

            <div class="grid gap-1.5">
                <Label :for="`slide-subheading-${index}`">Subjudul</Label>
                <Textarea
                    :id="`slide-subheading-${index}`"
                    v-model="slide.subheading"
                    rows="2"
                    maxlength="300"
                />
            </div>

            <div class="grid gap-2">
                <span class="text-sm font-medium">Foto</span>
                <img
                    v-if="previewUrl(slide)"
                    :src="previewUrl(slide) ?? undefined"
                    :alt="
                        slide.image_alt ?? `Pratinjau foto slide ${index + 1}`
                    "
                    class="aspect-video w-full rounded-md border object-cover"
                />
                <label
                    class="inline-flex w-fit cursor-pointer items-center rounded-md border px-3 py-1.5 text-sm font-medium focus-within:ring-2 focus-within:ring-ring hover:bg-accent"
                >
                    {{
                        upload.processing && uploadingIndex === index
                            ? `Mengunggah… ${upload.progress?.percentage ?? 0}%`
                            : previewUrl(slide)
                              ? 'Ganti foto'
                              : 'Unggah foto'
                    }}
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        :disabled="upload.processing"
                        @change="onFileChange($event, index)"
                    />
                </label>
                <InputError
                    :message="
                        (uploadingIndex === index
                            ? upload.errors.image
                            : undefined) ?? errors[`slides.${index}.image_path`]
                    "
                />
                <div v-if="previewUrl(slide)" class="grid gap-1.5">
                    <Label :for="`slide-alt-${index}`"
                        >Teks alternatif foto</Label
                    >
                    <Input
                        :id="`slide-alt-${index}`"
                        v-model="slide.image_alt"
                        maxlength="160"
                        placeholder="Kosongkan bila foto hanya hiasan"
                    />
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                <div class="grid gap-1.5">
                    <Label :for="`slide-cta-label-${index}`">Teks tombol</Label>
                    <Input
                        :id="`slide-cta-label-${index}`"
                        v-model="slide.cta_label"
                        maxlength="40"
                    />
                    <InputError
                        :message="errors[`slides.${index}.cta_label`]"
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label :for="`slide-cta-url-${index}`">Tautan tombol</Label>
                    <Input
                        :id="`slide-cta-url-${index}`"
                        v-model.trim="slide.cta_url"
                        maxlength="500"
                        placeholder="/layanan"
                    />
                    <InputError :message="errors[`slides.${index}.cta_url`]" />
                </div>
            </div>

            <div class="flex flex-wrap gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="index === 0"
                    @click="moveSlide(index, -1)"
                >
                    Naik
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="index === block.props.slides.length - 1"
                    @click="moveSlide(index, 1)"
                >
                    Turun
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="block.props.slides.length <= 1"
                    @click="removeSlide(index)"
                >
                    Hapus slide {{ index + 1 }}
                </Button>
            </div>
        </fieldset>

        <InputError :message="errors.slides" />

        <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="block.props.slides.length >= MAX_SLIDES"
            @click="addSlide"
        >
            Tambah slide
        </Button>

        <label class="flex min-h-6 items-center gap-2 text-sm">
            <input
                v-model="block.props.autoplay"
                type="checkbox"
                class="size-4 accent-primary"
            />
            Bergeser otomatis
        </label>
        <div v-if="block.props.autoplay" class="grid gap-1.5">
            <Label for="slider-interval">Jeda antarslide (detik)</Label>
            <Input
                id="slider-interval"
                v-model.number="block.props.interval"
                type="number"
                inputmode="numeric"
                min="5"
                max="15"
                aria-describedby="slider-interval-help"
            />
            <p id="slider-interval-help" class="text-xs text-muted-foreground">
                Pengunjung selalu bisa menjeda, dan slide tidak bergeser bagi
                yang memilih gerak dikurangi.
            </p>
            <InputError :message="errors.interval" />
        </div>

        <label class="flex min-h-6 items-center gap-2 text-sm">
            <input
                v-model="block.props.show_search"
                type="checkbox"
                class="size-4 accent-primary"
            />
            Tampilkan kolom cari layanan
        </label>
    </div>
</template>
