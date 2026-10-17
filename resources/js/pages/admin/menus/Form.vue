<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MenuController from '@/actions/App/Http/Controllers/Admin/MenuController';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { Option } from '@/types/admin';

type ParentOption = { id: number; label: string; depth: number };

const props = defineProps<{
    menu: {
        id: number;
        location: string;
        parent_id: number | null;
        label: string;
        page_id: number | null;
        url: string | null;
        open_in_new_tab: boolean;
        sort_order: number;
        is_active: boolean;
        excluded_parent_ids: number[];
        updated_at: string | null;
    } | null;
    defaultLocation: string;
    locations: Option[];
    pages: { id: number; title: string; status: string }[];
    parents: Record<string, ParentOption[]>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Menu', href: MenuController.index() }],
    },
});

const title = computed(() =>
    props.menu ? 'Ubah item menu' : 'Tambah item menu',
);
const formAction = computed(() =>
    props.menu
        ? MenuController.update.form(props.menu.id)
        : MenuController.store.form(),
);
const formKey = computed(() =>
    props.menu ? `${props.menu.id}-${props.menu.updated_at}` : 'new',
);

const location = ref(props.menu?.location ?? props.defaultLocation);
const target = ref<'page' | 'url'>(props.menu?.url ? 'url' : 'page');

// Item ini dan keturunannya tidak boleh menjadi induk (mencegah siklus).
const parentOptions = computed(() =>
    (props.parents[location.value] ?? []).filter(
        (option) => !props.menu?.excluded_parent_ids.includes(option.id),
    ),
);

function indent(depth: number): string {
    return '— '.repeat(depth - 1);
}
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-2xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="title" />

        <Form
            :key="formKey"
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="label">Label</Label>
                <Input
                    id="label"
                    name="label"
                    required
                    maxlength="100"
                    :default-value="menu?.label"
                    :aria-invalid="!!errors.label"
                />
                <InputError :message="errors.label" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="location">Lokasi</Label>
                    <NativeSelect
                        id="location"
                        v-model="location"
                        name="location"
                    >
                        <option
                            v-for="option in locations"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.location" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="parent_id">Induk</Label>
                    <NativeSelect
                        id="parent_id"
                        name="parent_id"
                        :default-value="menu?.parent_id ?? ''"
                    >
                        <option value="">Tanpa induk (tingkat atas)</option>
                        <option
                            v-for="option in parentOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ indent(option.depth) }}{{ option.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.parent_id" />
                </div>
            </div>

            <fieldset class="grid gap-3">
                <legend class="mb-1 text-sm font-medium">Tujuan tautan</legend>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="flex min-h-6 items-center gap-2">
                        <input
                            v-model="target"
                            type="radio"
                            value="page"
                            class="size-4 accent-primary"
                        />
                        Halaman situs
                    </label>
                    <label class="flex min-h-6 items-center gap-2">
                        <input
                            v-model="target"
                            type="radio"
                            value="url"
                            class="size-4 accent-primary"
                        />
                        URL kustom
                    </label>
                </div>

                <div v-if="target === 'page'" class="grid gap-1.5">
                    <Label for="page_id">Halaman</Label>
                    <NativeSelect
                        id="page_id"
                        name="page_id"
                        :default-value="menu?.page_id ?? ''"
                    >
                        <option value="">Pilih halaman</option>
                        <option
                            v-for="page in pages"
                            :key="page.id"
                            :value="page.id"
                        >
                            {{ page.title
                            }}{{ page.status === 'draft' ? ' (draf)' : '' }}
                        </option>
                    </NativeSelect>
                    <p class="text-xs text-muted-foreground">
                        Item yang menautkan halaman draf tidak tampil di situs
                        sampai halamannya terbit.
                    </p>
                    <InputError :message="errors.page_id" />
                </div>

                <div v-else class="grid gap-1.5">
                    <Label for="url">URL</Label>
                    <Input
                        id="url"
                        name="url"
                        maxlength="500"
                        placeholder="/ppid atau https://singkawangkota.go.id"
                        aria-describedby="url-help"
                        :default-value="menu?.url ?? ''"
                        :aria-invalid="!!errors.url"
                    />
                    <p id="url-help" class="text-xs text-muted-foreground">
                        Diawali "/" untuk halaman internal, atau "https://"
                        untuk situs lain.
                    </p>
                    <InputError :message="errors.url" />
                </div>
            </fieldset>

            <div class="grid gap-1.5 sm:max-w-40">
                <Label for="sort_order">Urutan</Label>
                <Input
                    id="sort_order"
                    name="sort_order"
                    type="number"
                    inputmode="numeric"
                    min="0"
                    max="9999"
                    :default-value="menu?.sort_order ?? 0"
                />
                <InputError :message="errors.sort_order" />
            </div>

            <div class="grid gap-2 text-sm">
                <label class="flex min-h-6 items-center gap-2">
                    <input type="hidden" name="open_in_new_tab" value="0" />
                    <input
                        type="checkbox"
                        name="open_in_new_tab"
                        value="1"
                        :checked="menu?.open_in_new_tab ?? false"
                        class="size-4 accent-primary"
                    />
                    Buka di tab baru
                </label>
                <label class="flex min-h-6 items-center gap-2">
                    <input type="hidden" name="is_active" value="0" />
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        :checked="menu?.is_active ?? true"
                        class="size-4 accent-primary"
                    />
                    Aktif
                </label>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="MenuController.index({ query: { location } })"
                        >Batal</Link
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
