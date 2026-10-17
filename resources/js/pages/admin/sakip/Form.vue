<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import SakipController from '@/actions/App/Http/Controllers/Admin/SakipController';
import FileField from '@/components/admin/FileField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { formatBytes } from '@/lib/format';
import type { Option } from '@/types/admin';

const props = defineProps<{
    document: {
        id: number;
        year: number;
        document_type: string;
        document_title: string;
        file_size: number;
        is_published: boolean;
        updated_at: string | null;
    } | null;
    types: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'E-SAKIP', href: SakipController.index() }],
    },
});

const title = computed(() =>
    props.document ? 'Ubah dokumen SAKIP' : 'Unggah dokumen SAKIP',
);
const formAction = computed(() =>
    props.document
        ? SakipController.update.form(props.document.id)
        : SakipController.store.form(),
);
const formKey = computed(() =>
    props.document
        ? `${props.document.id}-${props.document.updated_at}`
        : 'new',
);
const currentYear = new Date().getFullYear();
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
            <div class="grid gap-5 sm:grid-cols-[9rem_1fr]">
                <div class="grid gap-1.5">
                    <Label for="year">Tahun</Label>
                    <Input
                        id="year"
                        name="year"
                        type="number"
                        inputmode="numeric"
                        min="2000"
                        max="2100"
                        required
                        :default-value="document?.year ?? currentYear"
                        :aria-invalid="!!errors.year"
                    />
                    <InputError :message="errors.year" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="document_type">Jenis dokumen</Label>
                    <NativeSelect
                        id="document_type"
                        name="document_type"
                        required
                        :default-value="document?.document_type ?? 'lkjip'"
                    >
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.document_type" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="document_title">Judul dokumen</Label>
                <Input
                    id="document_title"
                    name="document_title"
                    required
                    maxlength="255"
                    placeholder="mis. Laporan Kinerja Instansi Pemerintah Tahun 2025"
                    :default-value="document?.document_title"
                    :aria-invalid="!!errors.document_title"
                />
                <InputError :message="errors.document_title" />
            </div>

            <FileField
                id="file"
                name="file"
                :label="document ? 'Ganti berkas PDF (opsional)' : 'Berkas PDF'"
                accept="application/pdf"
                help="Hanya PDF, maks. 20 MB. Berkas disimpan privat dan hanya bisa diunduh publik setelah diterbitkan."
                :required="!document"
                :error="errors.file"
                :progress="progress"
            >
                <template v-if="document" #current>
                    <p class="text-sm">
                        Berkas saat ini: PDF
                        {{ formatBytes(document.file_size) }} ·
                        <a
                            :href="SakipController.file.url(document.id)"
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
                    <Link :href="SakipController.index()">Batal</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
