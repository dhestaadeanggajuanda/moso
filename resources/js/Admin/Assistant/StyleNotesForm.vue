<script setup lang="ts">
/**
 * Aturan gaya tulisan yang dipelajari asisten AI dari koreksi editor. Pengelola
 * boleh menyunting, mengunci (agar tidak diperbarui otomatis), atau mengosongkannya.
 */
import { router, useForm } from '@inertiajs/vue3';
import { Lock, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import AssistantController from '@/actions/App/Http/Controllers/Admin/AssistantController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import type { AssistantStyleNotes } from '@/types/admin';

const props = defineProps<{
    styleNotes: AssistantStyleNotes;
}>();

const MAX_NOTES = 12;

const form = useForm({
    notes: [...props.styleNotes.notes],
    locked: props.styleNotes.locked,
});

const resetOpen = ref(false);
const resetting = ref(false);
const resetError = ref<string | null>(null);

function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}

function submit(): void {
    form.put(AssistantController.updateStyleNotes.url(), {
        preserveScroll: true,
        preserveState: true,
    });
}

function reset(): void {
    router.visit(AssistantController.resetStyleNotes(), {
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            resetting.value = true;
            resetError.value = null;
        },
        // Server sudah mengosongkan aturan dan membuka kunci; samakan isian form tanpa memuat ulang.
        onSuccess: () => {
            form.notes = [];
            form.locked = false;
            form.defaults();
            form.clearErrors();
        },
        // Galat dari server (mis. agentic tidak terjangkau) tidak terikat ke field form.
        onError: (errors) => (resetError.value = errors.agentic ?? null),
        onFinish: () => {
            resetting.value = false;
            resetOpen.value = false;
        },
    });
}
</script>

<template>
    <form class="grid gap-5" @submit.prevent="submit">
        <p class="text-sm text-muted-foreground">
            Dipelajari dari {{ styleNotes.sample_count }} koreksi editor.
            Terakhir diperbarui: {{ formatDateTime(styleNotes.updated_at) }}.
        </p>

        <ol class="grid gap-3">
            <li
                v-for="(_, index) in form.notes"
                :key="index"
                class="flex items-start gap-2"
            >
                <div class="grid flex-1 gap-1">
                    <Label :for="`style-note-${index}`" class="sr-only">
                        Aturan {{ index + 1 }}
                    </Label>
                    <Textarea
                        :id="`style-note-${index}`"
                        v-model="form.notes[index]"
                        rows="2"
                        required
                        maxlength="300"
                        :aria-invalid="!!errorFor(`notes.${index}`)"
                    />
                    <InputError :message="errorFor(`notes.${index}`)" />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="text-destructive"
                    @click="form.notes.splice(index, 1)"
                >
                    <Trash2 aria-hidden="true" />
                    <span class="sr-only">Hapus aturan {{ index + 1 }}</span>
                </Button>
            </li>
        </ol>
        <p v-if="form.notes.length === 0" class="text-sm text-muted-foreground">
            Belum ada aturan. Asisten akan belajar setelah beberapa draf
            disunting editor dan diterbitkan.
        </p>

        <div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="form.notes.length >= MAX_NOTES"
                @click="form.notes.push('')"
            >
                <Plus aria-hidden="true" />
                Tambah aturan
            </Button>
        </div>
        <InputError :message="form.errors.notes" />

        <label class="flex min-h-6 items-start gap-2 text-sm">
            <input
                v-model="form.locked"
                type="checkbox"
                class="mt-0.5 size-4 accent-primary"
                aria-describedby="style-locked-help"
            />
            <span class="grid gap-0.5">
                <span class="flex items-center gap-1.5 font-medium">
                    <Lock class="size-3.5" aria-hidden="true" />
                    Kunci aturan
                </span>
                <span id="style-locked-help" class="text-muted-foreground">
                    Aturan tidak lagi diperbarui otomatis dari koreksi editor.
                </span>
            </span>
        </label>

        <InputError :message="errorFor('agentic') ?? resetError ?? undefined" />

        <div class="flex flex-wrap items-center gap-2">
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan…' : 'Simpan gaya tulisan' }}
            </Button>

            <Dialog v-model:open="resetOpen">
                <DialogTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        class="text-destructive"
                    >
                        Reset
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Kosongkan gaya tulisan?</DialogTitle>
                        <DialogDescription>
                            Semua aturan hasil belajar dihapus dan kunci dibuka.
                            Asisten akan belajar ulang dari koreksi editor
                            berikutnya.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Batal</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            :disabled="resetting"
                            @click="reset"
                        >
                            Ya, kosongkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </form>
</template>
