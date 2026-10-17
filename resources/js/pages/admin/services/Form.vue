<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ServiceController from '@/actions/App/Http/Controllers/Admin/ServiceController';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    service: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        requirements: string;
        procedure: string;
        processing_time: string;
        cost: string;
        product: string | null;
        complaint_channel: string | null;
        sort_order: number;
        is_published: boolean;
        updated_at: string | null;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Layanan', href: ServiceController.index() }],
    },
});

const title = computed(() =>
    props.service ? 'Ubah layanan' : 'Tambah layanan',
);
const formAction = computed(() =>
    props.service
        ? ServiceController.update.form(props.service.id)
        : ServiceController.store.form(),
);
const formKey = computed(() =>
    props.service ? `${props.service.id}-${props.service.updated_at}` : 'new',
);

/**
 * Pesan error butir list (mis. "requirements.2") ditampilkan di bawah textarea-nya.
 */
function listError(
    errors: Record<string, string>,
    field: string,
): string | undefined {
    return (
        errors[field] ??
        Object.entries(errors).find(([key]) => key.startsWith(`${field}.`))?.[1]
    );
}
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="title"
            description="Komponen Standar Pelayanan sesuai Permenpan RB 15/2014."
        />

        <Form
            :key="formKey"
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="name">Nama layanan</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    maxlength="255"
                    :default-value="service?.name"
                    :aria-invalid="!!errors.name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="3"
                    :default-value="service?.description ?? ''"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-1.5">
                <Label for="requirements">Persyaratan</Label>
                <Textarea
                    id="requirements"
                    name="requirements"
                    rows="5"
                    required
                    aria-describedby="requirements-help"
                    :default-value="service?.requirements ?? ''"
                    :aria-invalid="!!listError(errors, 'requirements')"
                />
                <p id="requirements-help" class="text-xs text-muted-foreground">
                    Satu baris untuk satu persyaratan.
                </p>
                <InputError :message="listError(errors, 'requirements')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="procedure">Sistem, mekanisme, dan prosedur</Label>
                <Textarea
                    id="procedure"
                    name="procedure"
                    rows="5"
                    required
                    aria-describedby="procedure-help"
                    :default-value="service?.procedure ?? ''"
                    :aria-invalid="!!listError(errors, 'procedure')"
                />
                <p id="procedure-help" class="text-xs text-muted-foreground">
                    Satu baris untuk satu langkah, urut dari awal sampai
                    selesai.
                </p>
                <InputError :message="listError(errors, 'procedure')" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="processing_time"
                        >Jangka waktu penyelesaian</Label
                    >
                    <Input
                        id="processing_time"
                        name="processing_time"
                        required
                        maxlength="100"
                        placeholder="mis. 3 hari kerja"
                        :default-value="service?.processing_time"
                        :aria-invalid="!!errors.processing_time"
                    />
                    <InputError :message="errors.processing_time" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="cost">Biaya/tarif</Label>
                    <Input
                        id="cost"
                        name="cost"
                        required
                        maxlength="100"
                        placeholder="mis. Gratis"
                        :default-value="service?.cost"
                        :aria-invalid="!!errors.cost"
                    />
                    <InputError :message="errors.cost" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="product">Produk layanan</Label>
                <Input
                    id="product"
                    name="product"
                    maxlength="255"
                    placeholder="mis. Surat keterangan"
                    :default-value="service?.product ?? ''"
                />
                <InputError :message="errors.product" />
            </div>

            <div class="grid gap-1.5">
                <Label for="complaint_channel"
                    >Penanganan pengaduan, saran, dan masukan</Label
                >
                <Textarea
                    id="complaint_channel"
                    name="complaint_channel"
                    rows="3"
                    :default-value="service?.complaint_channel ?? ''"
                />
                <InputError :message="errors.complaint_channel" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="slug">Slug (opsional)</Label>
                    <Input
                        id="slug"
                        name="slug"
                        placeholder="dibuat otomatis dari nama"
                        :default-value="service?.slug"
                        :aria-invalid="!!errors.slug"
                    />
                    <InputError :message="errors.slug" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="sort_order">Urutan tampil</Label>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        max="9999"
                        :default-value="service?.sort_order ?? 0"
                    />
                    <InputError :message="errors.sort_order" />
                </div>
            </div>

            <label class="flex min-h-6 items-center gap-2 text-sm">
                <input type="hidden" name="is_published" value="0" />
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    :checked="service?.is_published ?? false"
                    class="size-4 accent-primary"
                />
                Tampilkan di situs publik
            </label>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="ServiceController.index()">Batal</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
