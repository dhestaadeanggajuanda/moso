<script setup lang="ts">
/**
 * Daftar identitas SSO yang tertaut ke akun, dengan aksi lepas tautan.
 * Tautan dibuat otomatis saat login SSO pertama (email terverifikasi di IdP).
 */
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
} from '@/components/ui/dialog';
import { formatDateTime } from '@/lib/format';
import type { SsoIdentityRow } from '@/types/admin';

const props = defineProps<{
    userId: number;
    identities: SsoIdentityRow[];
    ssoLabel: string;
}>();

const target = ref<SsoIdentityRow | null>(null);
const processing = ref(false);

function setOpen(value: boolean): void {
    if (!value) {
        target.value = null;
    }
}

function unlink(): void {
    if (!target.value) {
        return;
    }

    router.visit(
        UserController.unlinkSso({
            user: props.userId,
            ssoIdentity: target.value.id,
        }),
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => {
                processing.value = false;
                target.value = null;
            },
        },
    );
}
</script>

<template>
    <div>
        <p v-if="identities.length === 0" class="text-sm text-muted-foreground">
            Belum tertaut. Tautan dibuat otomatis saat pegawai pertama kali
            masuk lewat {{ ssoLabel }} dengan email yang sama dan sudah
            terverifikasi di sana.
        </p>
        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="identity in identities"
                :key="identity.id"
                class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0 text-sm">
                    <p class="font-medium">{{ identity.provider }}</p>
                    <p class="text-muted-foreground">
                        Tertaut {{ formatDateTime(identity.created_at) }} ·
                        login terakhir
                        {{ formatDateTime(identity.last_login_at) }}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-destructive"
                    @click="target = identity"
                >
                    Lepas tautan
                    <span class="sr-only">{{ identity.provider }}</span>
                </Button>
            </li>
        </ul>

        <Dialog :open="target !== null" @update:open="setOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Lepas tautan SSO?</DialogTitle>
                    <DialogDescription>
                        Akun IdP yang sekarang tidak lagi bisa masuk ke akun
                        ini. Login SSO berikutnya akan menautkan ulang akun IdP
                        yang email terverifikasinya sama dengan email akun ini.
                        Untuk memutus akses sepenuhnya, nonaktifkan akun.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="processing"
                        @click="unlink"
                    >
                        Ya, lepas tautan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
