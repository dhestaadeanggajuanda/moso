<script setup lang="ts">
/**
 * Tombol aksi alur persetujuan artikel. Daftar aksi dihitung ArticlePolicy di
 * server; komponen ini tidak memutuskan hak akses, hanya menampilkan tombol
 * dan meminta konfirmasi (plus catatan atau jadwal bila perlu).
 */
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ArticleWorkflowController from '@/actions/App/Http/Controllers/Admin/ArticleWorkflowController';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { WorkflowAction } from '@/types/admin';

const props = defineProps<{
    articleId: number;
    articleTitle: string;
    actions: WorkflowAction[];
}>();

const active = ref<WorkflowAction | null>(null);
const open = computed({
    get: () => active.value !== null,
    set: (value: boolean) => {
        if (!value) {
            active.value = null;
        }
    },
});

const descriptions: Record<WorkflowAction['value'], string> = {
    submit: 'Artikel dikirim ke Editor untuk direview. Selama menunggu review, isinya tidak bisa diubah penulis.',
    approve:
        'Artikel ditandai siap terbit dan diteruskan ke Approver untuk diterbitkan.',
    request_revision:
        'Artikel dikembalikan ke penulis. Tulis apa yang perlu diperbaiki.',
    publish:
        'Artikel tampil di situs publik. Kosongkan jadwal untuk terbit sekarang.',
    archive: 'Artikel diturunkan dari situs publik, tetapi tetap tersimpan.',
    unarchive: 'Artikel tampil kembali di situs publik.',
};

const variants: Record<
    WorkflowAction['value'],
    'default' | 'outline' | 'destructive'
> = {
    submit: 'default',
    approve: 'default',
    request_revision: 'outline',
    publish: 'default',
    archive: 'outline',
    unarchive: 'outline',
};

const formAction = computed(() =>
    ArticleWorkflowController.store.form(props.articleId),
);
</script>

<template>
    <div>
        <div class="flex flex-wrap gap-2">
            <Button
                v-for="action in actions"
                :key="action.value"
                type="button"
                :variant="variants[action.value]"
                @click="active = action"
            >
                {{ action.label }}
            </Button>
        </div>

        <Dialog v-model:open="open">
            <DialogContent v-if="active">
                <Form
                    v-bind="formAction"
                    :options="{ preserveScroll: true }"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    @success="active = null"
                >
                    <DialogHeader>
                        <DialogTitle>{{ active.label }}?</DialogTitle>
                        <DialogDescription>
                            "{{ articleTitle }}".
                            {{ descriptions[active.value] }}
                        </DialogDescription>
                    </DialogHeader>

                    <input type="hidden" name="action" :value="active.value" />

                    <div class="grid gap-1.5">
                        <Label for="workflow-note">
                            {{
                                active.requires_note
                                    ? 'Catatan revisi'
                                    : 'Catatan (opsional)'
                            }}
                        </Label>
                        <Textarea
                            id="workflow-note"
                            name="note"
                            rows="4"
                            maxlength="2000"
                            :required="active.requires_note"
                            :aria-invalid="!!errors.note"
                            :placeholder="
                                active.requires_note
                                    ? 'mis. Lengkapi sumber data pada paragraf kedua.'
                                    : ''
                            "
                        />
                        <InputError :message="errors.note" />
                    </div>

                    <div v-if="active.allows_schedule" class="grid gap-1.5">
                        <Label for="workflow-published-at">
                            Jadwal terbit (opsional, WIB)
                        </Label>
                        <Input
                            id="workflow-published-at"
                            name="published_at"
                            type="datetime-local"
                            :aria-invalid="!!errors.published_at"
                            aria-describedby="workflow-published-at-help"
                        />
                        <p
                            id="workflow-published-at-help"
                            class="text-xs text-muted-foreground"
                        >
                            Kosongkan untuk terbit sekarang. Artikel terjadwal
                            baru tampil di situs pada waktunya.
                        </p>
                        <InputError :message="errors.published_at" />
                    </div>

                    <InputError :message="errors.action" />

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Memproses…' : active.label }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
