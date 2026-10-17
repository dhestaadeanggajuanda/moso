<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SettingRow = {
    key: string;
    value: string | null;
    is_encrypted: boolean;
    has_value: boolean;
};

defineProps<{
    settings: SettingRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Pengaturan situs', href: SettingController.index() },
        ],
    },
});

function inputId(key: string): string {
    return `setting-${key.replaceAll('.', '-')}`;
}
</script>

<template>
    <Head title="Pengaturan situs" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Pengaturan situs"
            description="Identitas, kontak, dan tema situs. Hanya Super Admin yang dapat mengubah halaman ini."
        />

        <Form
            v-bind="SettingController.update.form()"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div
                v-for="(setting, index) in settings"
                :key="setting.key"
                class="grid gap-1.5"
            >
                <input
                    type="hidden"
                    :name="`settings[${index}][key]`"
                    :value="setting.key"
                />
                <input
                    type="hidden"
                    :name="`settings[${index}][is_encrypted]`"
                    :value="setting.is_encrypted ? 1 : 0"
                />
                <Label
                    :for="inputId(setting.key)"
                    class="flex items-center gap-1.5 font-mono text-xs"
                >
                    <Lock
                        v-if="setting.is_encrypted"
                        class="size-3.5"
                        aria-hidden="true"
                    />
                    {{ setting.key }}
                    <span v-if="setting.is_encrypted" class="sr-only"
                        >(terenkripsi)</span
                    >
                </Label>
                <Input
                    :id="inputId(setting.key)"
                    :name="`settings[${index}][value]`"
                    :type="setting.is_encrypted ? 'password' : 'text'"
                    autocomplete="off"
                    :default-value="setting.value ?? ''"
                    :placeholder="
                        setting.is_encrypted && setting.has_value
                            ? 'Tersimpan. Kosongkan bila tidak diubah.'
                            : ''
                    "
                />
                <InputError :message="errors[`settings.${index}.value`]" />
            </div>

            <InputError :message="errors.settings" />

            <div>
                <Button type="submit" :disabled="processing">
                    Simpan pengaturan
                </Button>
            </div>
        </Form>
    </div>
</template>
