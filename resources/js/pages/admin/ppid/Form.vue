<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicDocumentController from '@/actions/App/Http/Controllers/Admin/PublicDocumentController';
import FileField from '@/components/admin/FileField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { formatBytes } from '@/lib/format';
import type { Option } from '@/types/admin';

const props = defineProps<{
    document: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        category: string;
        year: number | null;
        file_size: number;
        is_published: boolean;
        updated_at: string | null;
    } | null;
    categories: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dokumen PPID', href: PublicDocumentController.index() },
        ],
    },
});

const title = computed(() =>
    props.document ? 'Ubah dokumen PPID' : 'Unggah dokumen PPID',
);
const formAction = computed(() =>
    props.document
        ? PublicDocumentController.update.form(props.document.id)
        : PublicDocumentController.store.form(),
);
const formKey = computed(() =>
    props.document
        ? `${props.document.id}-${props.document.updated_at}`
        : 'new',
);
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="title" />

        <Form
            :key="formKey"
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing, progress }"
        >
            <div class="grid gap-1.5">
                <Label for="title">Judul dokumen</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    maxlength="255"
                    :default-value="document?.title"
                    :aria-invalid="!!errors.title"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-5 sm:grid-cols-[1fr_9rem]">
                <div class="grid gap-1.5">
                    <Label for="category">Kategori informasi</Label>
                    <NativeSelect
                        id="category"
                        name="category"
                        required
                        :default-value="document?.category ?? 'berkala'"
                    >
                        <option
                            v-for="category in categories"
                            :key="category.value"
                            :value="category.value"
                        >
                            {{ category.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.category" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="year">Tahun</Label>
                    <Input
                        id="year"
                        name="year"
                        type="number"
                        inputmode="numeric"
                        min="2000"
                        max="2100"
                        :default-value="document?.year ?? ''"
                        :aria-invalid="!!errors.year"
                    />
                    <InputError :message="errors.year" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="3"
                    :default-value="document?.description ?? ''"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-1.5">
                <Label for="slug">Slug (opsional)</Label>
                <Input
                    id="slug"
                    name="slug"
                    placeholder="dibuat otomatis dari judul"
                    :default-value="document?.slug"
                    :aria-invalid="!!errors.slug"
                />
                <InputError :message="errors.slug" />
            </div>

            <FileField
                id="file"
                name="file"
                :label="document ? 'Ganti berkas PDF (opsional)' : 'Berkas PDF'"
                accept="application/pdf"
                help="Hanya PDF, maks. 20 MB. Berkas disimpan privat dan hanya bisa diunduh publik setelah dokumen diterbitkan."
                :required="!document"
                :error="errors.file"
                :progress="progress"
            >
                <template v-if="document" #current>
                    <p class="text-sm">
                        Berkas saat ini: PDF
                        {{ formatBytes(document.file_size) }} ·
                        <a
                            :href="
                                PublicDocumentController.file.url(document.id)
                            "
                            target="_blank"
                            rel="noopener"
                            class="underline underline-offset-4"
                            >lihat berkas</a
                        >
                    </p>
                </template>
            </FileField>

            <label class="flex min-h-6 items-center gap-2 text-sm">
                <input type="hidden" name="is_published" value="0" />
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    :checked="document?.is_published ?? false"
                    class="size-4 accent-primary"
                />
                Terbitkan (dapat diunduh publik)
            </label>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="PublicDocumentController.index()">Batal</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
