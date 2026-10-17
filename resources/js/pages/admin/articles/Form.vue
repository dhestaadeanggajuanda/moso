<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ArticleController from '@/actions/App/Http/Controllers/Admin/ArticleController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { CategoryOption, StatusValue } from '@/types/admin';

type ArticleForm = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    image_url: string | null;
    image_alt: string | null;
    status: StatusValue;
    category_ids: number[];
    updated_at: string | null;
    can: { delete: boolean };
};

const props = defineProps<{
    article: ArticleForm | null;
    categories: CategoryOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Artikel', href: ArticleController.index() }],
    },
});

const isEdit = computed(() => props.article !== null);
const formAction = computed(() =>
    props.article
        ? ArticleController.update.form(props.article.id)
        : ArticleController.store.form(),
);
const removeImage = ref(false);

// Input memakai default-value (tidak terkontrol). Setelah simpan, Inertia memakai ulang
// komponen ini, jadi form dipasang ulang agar menampilkan nilai versi server
// (body tersanitasi, slug yang dibuat otomatis).
const formKey = computed(() =>
    props.article ? `${props.article.id}-${props.article.updated_at}` : 'new',
);
</script>

<template>
    <Head :title="isEdit ? 'Ubah artikel' : 'Tulis artikel'" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="isEdit ? 'Ubah artikel' : 'Tulis artikel'"
            description="Artikel baru tersimpan sebagai draf. Alur review dan terbit hadir di fase berikutnya."
        >
            <template #actions>
                <StatusBadge v-if="article" :status="article.status" />
            </template>
        </PageHeader>

        <Form
            :key="formKey"
            v-bind="formAction"
            class="grid gap-6 lg:grid-cols-[1fr_20rem]"
            v-slot="{ errors, processing, progress }"
        >
            <div class="grid content-start gap-5">
                <div class="grid gap-1.5">
                    <Label for="title">Judul</Label>
                    <Input
                        id="title"
                        name="title"
                        :default-value="article?.title"
                        required
                        maxlength="255"
                        :aria-invalid="!!errors.title"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="excerpt">Ringkasan</Label>
                    <Textarea
                        id="excerpt"
                        name="excerpt"
                        rows="2"
                        maxlength="500"
                        :default-value="article?.excerpt ?? ''"
                        :aria-invalid="!!errors.excerpt"
                    />
                    <InputError :message="errors.excerpt" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="body">Isi artikel</Label>
                    <Textarea
                        id="body"
                        name="body"
                        rows="16"
                        required
                        class="font-mono text-sm"
                        :default-value="article?.body ?? ''"
                        aria-describedby="body-help"
                        :aria-invalid="!!errors.body"
                    />
                    <p id="body-help" class="text-xs text-muted-foreground">
                        Mendukung HTML dasar (paragraf, judul, daftar, tautan,
                        gambar). Script, style, dan atribut berbahaya dibuang
                        otomatis saat disimpan.
                    </p>
                    <InputError :message="errors.body" />
                </div>
            </div>

            <aside class="grid content-start gap-5">
                <div class="grid gap-1.5">
                    <Label for="slug">Slug (opsional)</Label>
                    <Input
                        id="slug"
                        name="slug"
                        :default-value="article?.slug"
                        placeholder="dibuat otomatis dari judul"
                        :aria-invalid="!!errors.slug"
                    />
                    <InputError :message="errors.slug" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-1 text-sm font-medium">Kategori</legend>
                    <p
                        v-if="categories.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Belum ada kategori.
                    </p>
                    <label
                        v-for="category in categories"
                        :key="category.id"
                        class="flex min-h-6 items-center gap-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            name="category_ids[]"
                            :value="category.id"
                            :checked="
                                article?.category_ids.includes(category.id)
                            "
                            class="size-4 rounded border-input accent-primary"
                        />
                        {{ category.name }}
                    </label>
                    <InputError :message="errors.category_ids" />
                </fieldset>

                <div class="grid gap-1.5">
                    <Label for="image">Gambar utama</Label>
                    <img
                        v-if="article?.image_url && !removeImage"
                        :src="article.image_url"
                        :alt="article.image_alt ?? ''"
                        class="aspect-video w-full rounded-md border object-cover"
                    />
                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        aria-describedby="image-help"
                        class="text-sm file:mr-3 file:rounded-md file:border file:bg-transparent file:px-3 file:py-1.5 file:text-sm"
                    />
                    <p id="image-help" class="text-xs text-muted-foreground">
                        JPG, PNG, atau WebP, maks. 5 MB. Gambar dikonversi ke
                        WebP dan metadata lokasinya dihapus.
                    </p>
                    <progress
                        v-if="progress"
                        :value="progress.percentage"
                        max="100"
                        class="w-full"
                    >
                        {{ progress.percentage }}%
                    </progress>
                    <InputError :message="errors.image" />
                    <label
                        v-if="article?.image_url"
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

                <div class="grid gap-1.5">
                    <Label for="image_alt">Teks alternatif gambar</Label>
                    <Input
                        id="image_alt"
                        name="image_alt"
                        :default-value="article?.image_alt ?? ''"
                        aria-describedby="image-alt-help"
                        :aria-invalid="!!errors.image_alt"
                    />
                    <p
                        id="image-alt-help"
                        class="text-xs text-muted-foreground"
                    >
                        Wajib bila ada gambar: jelaskan isi gambar untuk pembaca
                        layar.
                    </p>
                    <InputError :message="errors.image_alt" />
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t pt-4">
                    <Button type="submit" :disabled="processing">
                        {{ isEdit ? 'Simpan perubahan' : 'Simpan draf' }}
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="ArticleController.index()">Kembali</Link>
                    </Button>
                    <ConfirmDelete
                        v-if="article?.can.delete"
                        :action="ArticleController.destroy(article.id)"
                        :item-name="article.title"
                    />
                </div>
            </aside>
        </Form>
    </div>
</template>
