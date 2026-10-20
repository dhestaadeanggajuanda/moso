<script setup lang="ts">
/**
 * Profil dinas untuk asisten AI. Agentic memakainya sebagai sumber fakta tetap
 * (nama resmi, tugas, pejabat), jadi nama pejabat di draf hanya diambil dari sini.
 */
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import AssistantController from '@/actions/App/Http/Controllers/Admin/AssistantController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import type { AssistantProfile } from '@/types/admin';

const props = defineProps<{
    profile: AssistantProfile;
}>();

const MAX_ITEMS = 30;

const form = useForm({
    official_name: props.profile.official_name,
    short_name: props.profile.short_name,
    duties: props.profile.duties,
    officials: props.profile.officials.map((official) => ({ ...official })),
    programs: [...props.profile.programs],
    address: props.profile.address,
    contact: props.profile.contact,
    style_guide: props.profile.style_guide,
});

function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}

function submit(): void {
    form.put(AssistantController.updateProfile.url(), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="submit">
        <p class="text-sm text-muted-foreground">
            Terakhir diperbarui: {{ formatDateTime(profile.updated_at) }}
        </p>

        <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
            <div class="grid gap-1.5">
                <Label for="profile-official-name">Nama resmi dinas</Label>
                <Input
                    id="profile-official-name"
                    v-model="form.official_name"
                    required
                    maxlength="200"
                    placeholder="mis. Dinas Kesehatan Kota Singkawang"
                    :aria-invalid="!!form.errors.official_name"
                />
                <InputError :message="form.errors.official_name" />
            </div>
            <div class="grid gap-1.5">
                <Label for="profile-short-name">Singkatan</Label>
                <Input
                    id="profile-short-name"
                    v-model="form.short_name"
                    maxlength="50"
                    placeholder="mis. Dinkes"
                    :aria-invalid="!!form.errors.short_name"
                />
                <InputError :message="form.errors.short_name" />
            </div>
        </div>

        <div class="grid gap-1.5">
            <Label for="profile-duties">Tugas dan fungsi</Label>
            <Textarea
                id="profile-duties"
                v-model="form.duties"
                rows="4"
                maxlength="3000"
                :aria-invalid="!!form.errors.duties"
            />
            <InputError :message="form.errors.duties" />
        </div>

        <fieldset class="grid gap-3">
            <legend class="mb-1 text-sm font-medium">Pejabat</legend>
            <p class="-mt-2 text-xs text-muted-foreground">
                Asisten hanya menyebut nama pejabat yang tercantum di sini.
            </p>
            <div
                v-for="(official, index) in form.officials"
                :key="index"
                class="grid gap-2 rounded-md border p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start"
            >
                <div class="grid gap-1">
                    <Label :for="`official-name-${index}`" class="sr-only">
                        Nama pejabat {{ index + 1 }}
                    </Label>
                    <Input
                        :id="`official-name-${index}`"
                        v-model="official.name"
                        required
                        maxlength="150"
                        placeholder="Nama dan gelar"
                        :aria-invalid="!!errorFor(`officials.${index}.name`)"
                    />
                    <InputError
                        :message="errorFor(`officials.${index}.name`)"
                    />
                </div>
                <div class="grid gap-1">
                    <Label :for="`official-position-${index}`" class="sr-only">
                        Jabatan pejabat {{ index + 1 }}
                    </Label>
                    <Input
                        :id="`official-position-${index}`"
                        v-model="official.position"
                        required
                        maxlength="150"
                        placeholder="Jabatan, mis. Kepala Dinas"
                        :aria-invalid="
                            !!errorFor(`officials.${index}.position`)
                        "
                    />
                    <InputError
                        :message="errorFor(`officials.${index}.position`)"
                    />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="text-destructive"
                    @click="form.officials.splice(index, 1)"
                >
                    <Trash2 aria-hidden="true" />
                    <span class="sr-only">Hapus pejabat {{ index + 1 }}</span>
                </Button>
            </div>
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="form.officials.length >= MAX_ITEMS"
                    @click="form.officials.push({ name: '', position: '' })"
                >
                    <Plus aria-hidden="true" />
                    Tambah pejabat
                </Button>
            </div>
            <InputError :message="form.errors.officials" />
        </fieldset>

        <fieldset class="grid gap-3">
            <legend class="mb-1 text-sm font-medium">Program rutin</legend>
            <div
                v-for="(_, index) in form.programs"
                :key="index"
                class="flex items-start gap-2"
            >
                <div class="grid flex-1 gap-1">
                    <Label :for="`program-${index}`" class="sr-only">
                        Program {{ index + 1 }}
                    </Label>
                    <Input
                        :id="`program-${index}`"
                        v-model="form.programs[index]"
                        required
                        maxlength="200"
                        placeholder="mis. Posyandu balita bulanan"
                        :aria-invalid="!!errorFor(`programs.${index}`)"
                    />
                    <InputError :message="errorFor(`programs.${index}`)" />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="text-destructive"
                    @click="form.programs.splice(index, 1)"
                >
                    <Trash2 aria-hidden="true" />
                    <span class="sr-only">Hapus program {{ index + 1 }}</span>
                </Button>
            </div>
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="form.programs.length >= MAX_ITEMS"
                    @click="form.programs.push('')"
                >
                    <Plus aria-hidden="true" />
                    Tambah program
                </Button>
            </div>
            <InputError :message="form.errors.programs" />
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <Label for="profile-address">Alamat</Label>
                <Textarea
                    id="profile-address"
                    v-model="form.address"
                    rows="2"
                    maxlength="500"
                    :aria-invalid="!!form.errors.address"
                />
                <InputError :message="form.errors.address" />
            </div>
            <div class="grid gap-1.5">
                <Label for="profile-contact">Kontak</Label>
                <Textarea
                    id="profile-contact"
                    v-model="form.contact"
                    rows="2"
                    maxlength="500"
                    placeholder="Telepon, email, jam layanan"
                    :aria-invalid="!!form.errors.contact"
                />
                <InputError :message="form.errors.contact" />
            </div>
        </div>

        <div class="grid gap-1.5">
            <Label for="profile-style-guide">Catatan gaya (opsional)</Label>
            <Textarea
                id="profile-style-guide"
                v-model="form.style_guide"
                rows="3"
                maxlength="2000"
                aria-describedby="profile-style-guide-help"
                :aria-invalid="!!form.errors.style_guide"
            />
            <p
                id="profile-style-guide-help"
                class="text-xs text-muted-foreground"
            >
                Aturan tetap dari pengelola, mis. "Selalu tulis Kota Singkawang,
                bukan Singkawang saja".
            </p>
            <InputError :message="form.errors.style_guide" />
        </div>

        <InputError :message="errorFor('agentic')" />

        <div>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan…' : 'Simpan profil' }}
            </Button>
        </div>
    </form>
</template>
