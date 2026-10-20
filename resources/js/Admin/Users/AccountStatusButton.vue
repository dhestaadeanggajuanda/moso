<script setup lang="ts">
/**
 * Tombol nonaktifkan/aktifkan akun. Larangan menonaktifkan akun sendiri dan
 * hak aksesnya dicek di server (UserController); tombol hanya meminta konfirmasi.
 */
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
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

const props = defineProps<{
    userId: number;
    userName: string;
    isActive: boolean;
}>();

const open = ref(false);
const processing = ref(false);

function submit(): void {
    const action = props.isActive
        ? UserController.deactivate(props.userId)
        : UserController.activate(props.userId);

    router.visit(action, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onError: (errors) => {
            toast.error(errors.account ?? 'Status akun gagal diubah.');
        },
        onFinish: () => {
            processing.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-if="isActive" v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="ghost" size="sm" class="text-destructive">
                Nonaktifkan
                <span class="sr-only">{{ userName }}</span>
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Nonaktifkan akun "{{ userName }}"?</DialogTitle>
                <DialogDescription>
                    Pengguna langsung keluar dari semua perangkat dan tidak bisa
                    masuk lagi, baik dengan kata sandi, passkey, maupun SSO.
                    Artikel dan riwayatnya tetap tersimpan. Akun bisa diaktifkan
                    kembali kapan saja.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Batal</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="submit"
                >
                    Ya, nonaktifkan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
    <Button
        v-else
        variant="ghost"
        size="sm"
        :disabled="processing"
        @click="submit"
    >
        Aktifkan
        <span class="sr-only">{{ userName }}</span>
    </Button>
</template>
