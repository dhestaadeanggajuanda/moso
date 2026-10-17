<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    category: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kategori', href: CategoryController.index() }],
    },
});

const title = computed(() =>
    props.category ? 'Ubah kategori' : 'Tambah kategori',
);
const formAction = computed(() =>
    props.category
        ? CategoryController.update.form(props.category.id)
        : CategoryController.store.form(),
);
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-2xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="title" />

        <Form
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="name">Nama kategori</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    maxlength="100"
                    :default-value="category?.name"
                    :aria-invalid="!!errors.name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="slug">Slug (opsional)</Label>
                <Input
                    id="slug"
                    name="slug"
                    placeholder="dibuat otomatis dari nama"
                    :default-value="category?.slug"
                    :aria-invalid="!!errors.slug"
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-1.5">
                <Label for="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="3"
                    :default-value="category?.description ?? ''"
                    :aria-invalid="!!errors.description"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="CategoryController.index()">Batal</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
