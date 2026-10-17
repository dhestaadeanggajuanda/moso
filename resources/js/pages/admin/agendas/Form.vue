<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AgendaController from '@/actions/App/Http/Controllers/Admin/AgendaController';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    agenda: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        location: string | null;
        organizer: string | null;
        start_at: string;
        end_at: string | null;
        is_published: boolean;
        image_url: string | null;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agenda', href: AgendaController.index() }],
    },
});

const title = computed(() => (props.agenda ? 'Ubah agenda' : 'Tambah agenda'));
const formAction = computed(() =>
    props.agenda
        ? AgendaController.update.form(props.agenda.id)
        : AgendaController.store.form(),
);
const removeImage = ref(false);
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="title" />

        <Form
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="title">Judul kegiatan</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    maxlength="255"
                    :default-value="agenda?.title"
                    :aria-invalid="!!errors.title"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="start_at">Mulai</Label>
                    <Input
                        id="start_at"
                        name="start_at"
                        type="datetime-local"
                        required
                        :default-value="agenda?.start_at"
                        :aria-invalid="!!errors.start_at"
                    />
                    <InputError :message="errors.start_at" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="end_at">Selesai (opsional)</Label>
                    <Input
                        id="end_at"
                        name="end_at"
                        type="datetime-local"
                        :default-value="agenda?.end_at ?? ''"
                        :aria-invalid="!!errors.end_at"
                    />
                    <InputError :message="errors.end_at" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="location">Lokasi</Label>
                    <Input
                        id="location"
                        name="location"
                        :default-value="agenda?.location ?? ''"
                    />
                    <InputError :message="errors.location" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="organizer">Penyelenggara</Label>
                    <Input
                        id="organizer"
                        name="organizer"
                        :default-value="agenda?.organizer ?? ''"
                    />
                    <InputError :message="errors.organizer" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="4"
                    :default-value="agenda?.description ?? ''"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-1.5">
                <Label for="image">Gambar (opsional)</Label>
                <img
                    v-if="agenda?.image_url && !removeImage"
                    :src="agenda.image_url"
                    alt=""
                    class="aspect-video w-full max-w-sm rounded-md border object-cover"
                />
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="text-sm file:mr-3 file:rounded-md file:border file:bg-transparent file:px-3 file:py-1.5 file:text-sm"
                />
                <InputError :message="errors.image" />
                <label
                    v-if="agenda?.image_url"
                    class="flex items-center gap-2 text-sm"
                >
                    <input
                        v-model="removeImage"
                        type="checkbox"
                        name="remove_image"
                        value="1"
                        class="size-4 accent-primary"
                    />
                    Hapus gambar
                </label>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <!-- Nilai 0 terkirim bila kotak tidak dicentang. -->
                <input type="hidden" name="is_published" value="0" />
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    :checked="agenda?.is_published ?? true"
                    class="size-4 accent-primary"
                />
                Tampilkan di situs publik
            </label>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="AgendaController.index()">Batal</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
