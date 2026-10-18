<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Landmark, Lock } from '@lucide/vue';
import SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import SiteLogoController from '@/actions/App/Http/Controllers/Admin/SiteLogoController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import FileField from '@/components/admin/FileField.vue';
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

type LogoSlot = {
    slot: 'pemkot' | 'dinas';
    label: string;
    /** URL publik logo; null bila belum diunggah. */
    url: string | null;
};

defineProps<{
    settings: SettingRow[];
    logos: LogoSlot[];
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

    <div class="flex max-w-3xl flex-1 flex-col gap-8 p-4 md:p-6">
        <PageHeader
            title="Pengaturan situs"
            description="Identitas, kontak, dan tema situs. Hanya Super Admin yang dapat mengubah halaman ini."
        />

        <section aria-labelledby="pengaturan-logo" class="grid gap-4">
            <div>
                <h2 id="pengaturan-logo" class="text-base font-semibold">
                    Logo
                </h2>
                <p class="text-sm text-muted-foreground">
                    Tampil di header dan footer situs publik. Logo yang kosong
                    diganti lambang netral.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="logo in logos"
                    :key="logo.slot"
                    class="grid content-start gap-4 rounded-lg border p-4"
                >
                    <div
                        class="flex h-28 items-center justify-center rounded-md border bg-muted/50 p-3"
                    >
                        <img
                            v-if="logo.url"
                            :src="logo.url"
                            :alt="`${logo.label} saat ini`"
                            class="max-h-full max-w-full object-contain"
                        />
                        <p
                            v-else
                            class="flex flex-col items-center gap-1.5 text-center text-sm text-muted-foreground"
                        >
                            <Landmark class="size-6" aria-hidden="true" />
                            Belum ada logo
                        </p>
                    </div>

                    <Form
                        v-bind="SiteLogoController.update.form(logo.slot)"
                        :error-bag="`logo_${logo.slot}`"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        class="grid gap-3"
                        v-slot="{ errors, processing, progress }"
                    >
                        <FileField
                            :id="`logo-${logo.slot}`"
                            name="logo"
                            :label="logo.label"
                            accept="image/png,image/webp,image/jpeg"
                            help="PNG atau WebP transparan disarankan (JPG juga bisa), maks. 2 MB, minimal 64 px. Dikonversi ke WebP."
                            required
                            :progress="progress"
                            :error="errors.logo"
                        />
                        <div>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                            >
                                {{ logo.url ? 'Ganti logo' : 'Unggah logo' }}
                            </Button>
                        </div>
                    </Form>

                    <!-- Di luar <Form> agar tombolnya tidak ikut mengirim unggahan. -->
                    <div v-if="logo.url" class="-mt-2">
                        <ConfirmDelete
                            :action="SiteLogoController.destroy(logo.slot)"
                            :item-name="logo.label"
                            trigger-label="Hapus logo"
                        />
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="pengaturan-umum" class="grid gap-4">
            <h2 id="pengaturan-umum" class="text-base font-semibold">
                Identitas dan kontak
            </h2>

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
        </section>
    </div>
</template>
