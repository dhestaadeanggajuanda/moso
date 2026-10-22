<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageBuilderController from '@/actions/App/Http/Controllers/Admin/PageBuilderController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Option } from '@/types/admin';

const props = defineProps<{
    page: {
        id: number;
        title: string;
        slug: string;
        meta_description: string | null;
        status: string;
        theme: string | null;
        blocks_count: number;
        /** text = isi diketik di form ini; blocks = disusun di Page Builder. */
        layout: 'text' | 'blocks';
        /** Isi HTML halaman teks (sudah disanitasi server). */
        body: string;
        updated_at: string | null;
        public_url: string;
    } | null;
    statuses: Option[];
    themes: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Halaman', href: PageController.index() }],
    },
});

const title = computed(() => (props.page ? 'Ubah halaman' : 'Buat halaman'));
const formAction = computed(() =>
    props.page
        ? PageController.update.form(props.page.id)
        : PageController.store.form(),
);
// Halaman baru: pilih jenisnya. Halaman lama: jenis mengikuti isinya.
const newLayout = ref<'text' | 'blocks'>('text');
const layout = computed(() => props.page?.layout ?? newLayout.value);

// Pasang ulang form setelah simpan agar menampilkan nilai versi server (mis. slug otomatis).
const formKey = computed(() =>
    props.page ? `${props.page.id}-${props.page.updated_at}` : 'new',
);
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-2xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="title"
            :description="
                page
                    ? `${page.blocks_count} blok konten.`
                    : 'Halaman teks diisi langsung di sini, seperti WordPress. Halaman blok disusun di Page Builder sesudah disimpan.'
            "
        >
            <template v-if="page" #actions>
                <Button variant="outline" as-child>
                    <Link :href="PageBuilderController.edit(page.id)"
                        >Buka Page Builder</Link
                    >
                </Button>
                <Button
                    v-if="page.status === 'published'"
                    variant="ghost"
                    as-child
                >
                    <a :href="page.public_url" target="_blank" rel="noopener"
                        >Lihat di situs</a
                    >
                </Button>
            </template>
        </PageHeader>

        <Form
            :key="formKey"
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="title">Judul</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    maxlength="255"
                    :default-value="page?.title"
                    :aria-invalid="!!errors.title"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-1.5">
                <Label for="slug">Slug (opsional)</Label>
                <Input
                    id="slug"
                    name="slug"
                    placeholder="dibuat otomatis dari judul"
                    :default-value="page?.slug"
                    :aria-invalid="!!errors.slug"
                />
                <InputError :message="errors.slug" />
            </div>

            <fieldset v-if="!page" class="grid gap-2">
                <legend class="mb-1 text-sm font-medium">Jenis halaman</legend>
                <input type="hidden" name="layout" :value="newLayout" />
                <label
                    class="flex cursor-pointer gap-3 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:bg-muted/50"
                >
                    <input
                        v-model="newLayout"
                        type="radio"
                        value="text"
                        class="mt-1 size-4 accent-primary"
                    />
                    <span class="grid gap-0.5 text-sm">
                        <span class="font-medium">Halaman teks</span>
                        <span class="text-muted-foreground"
                            >Judul dan isi, mis. Profil, Visi Misi, Struktur
                            Organisasi.</span
                        >
                    </span>
                </label>
                <label
                    class="flex cursor-pointer gap-3 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:bg-muted/50"
                >
                    <input
                        v-model="newLayout"
                        type="radio"
                        value="blocks"
                        class="mt-1 size-4 accent-primary"
                    />
                    <span class="grid gap-0.5 text-sm">
                        <span class="font-medium">Halaman blok</span>
                        <span class="text-muted-foreground"
                            >Disusun dari banner, berita, layanan, dan blok lain
                            di Page Builder, mis. beranda.</span
                        >
                    </span>
                </label>
            </fieldset>

            <div v-if="layout === 'text'" class="grid gap-1.5">
                <Label for="body">Isi halaman</Label>
                <Textarea
                    id="body"
                    name="body"
                    rows="16"
                    class="font-mono text-sm"
                    :default-value="page?.body ?? ''"
                    aria-describedby="body-help"
                    :aria-invalid="!!errors.body"
                />
                <p id="body-help" class="text-xs text-muted-foreground">
                    Teks dan HTML sederhana (paragraf, judul, daftar, tautan,
                    tabel, gambar dari unggahan). Skrip dan gaya inline dibuang
                    otomatis.
                </p>
                <InputError :message="errors.body" />
            </div>

            <div class="grid gap-1.5">
                <Label for="status">Status</Label>
                <NativeSelect
                    id="status"
                    name="status"
                    :default-value="page?.status ?? 'draft'"
                >
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme">Tema</Label>
                <NativeSelect
                    id="theme"
                    name="theme"
                    :default-value="page?.theme ?? ''"
                >
                    <option value="">Ikuti tema aktif situs</option>
                    <option v-for="theme in themes" :key="theme" :value="theme">
                        {{ theme }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.theme" />
            </div>

            <div class="grid gap-1.5">
                <Label for="meta_description">Deskripsi meta (SEO)</Label>
                <Textarea
                    id="meta_description"
                    name="meta_description"
                    rows="2"
                    maxlength="300"
                    :default-value="page?.meta_description ?? ''"
                    :aria-invalid="!!errors.meta_description"
                />
                <InputError :message="errors.meta_description" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="PageController.index()">Kembali</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
