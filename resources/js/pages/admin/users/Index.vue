<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AccountStatusButton from '@/Admin/Users/AccountStatusButton.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { Paginated, RoleOption } from '@/types/admin';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    is_active: boolean;
    two_factor_enabled: boolean;
    sso_linked: boolean;
    is_self: boolean;
    can_update: boolean;
};

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: { search?: string; role?: string; status?: string };
    roles: RoleOption[];
    can: { create: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pengguna', href: UserController.index() }],
    },
});

const filter = reactive({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});

function applyFilters(): void {
    router.visit(
        UserController.index({
            query: {
                search: filter.search || undefined,
                role: filter.role || undefined,
                status: filter.status || undefined,
            },
        }),
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Pengguna" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Pengguna"
            description="Akun pegawai yang bisa masuk ke panel admin. Registrasi publik dimatikan, jadi akun hanya dibuat di sini."
        >
            <template v-if="can.create" #actions>
                <Button as-child>
                    <Link :href="UserController.create()">
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah pengguna
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form
            role="search"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_11rem_9rem_auto] lg:items-end"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-1.5 sm:col-span-2 lg:col-span-1">
                <Label for="search">Cari nama atau email</Label>
                <Input
                    id="search"
                    v-model="filter.search"
                    type="search"
                    maxlength="100"
                />
            </div>
            <div class="grid gap-1.5">
                <Label for="role">Peran</Label>
                <NativeSelect id="role" v-model="filter.role">
                    <option value="">Semua peran</option>
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                    >
                        {{ role.label }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="status">Status</Label>
                <NativeSelect id="status" v-model="filter.status">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </NativeSelect>
            </div>
            <Button type="submit" variant="secondary">Terapkan</Button>
        </form>

        <div
            v-if="users.data.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada pengguna yang cocok.
        </div>

        <template v-else>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="user in users.data"
                    :key="user.id"
                    class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="font-medium"
                                :class="{
                                    'text-muted-foreground': !user.is_active,
                                }"
                            >
                                {{ user.name }}
                                <span
                                    v-if="user.is_self"
                                    class="text-sm font-normal text-muted-foreground"
                                    >(Anda)</span
                                >
                            </p>
                            <Badge v-if="user.role" variant="secondary">{{
                                user.role
                            }}</Badge>
                            <Badge v-if="!user.is_active" variant="outline"
                                >Nonaktif</Badge
                            >
                        </div>
                        <p class="text-sm break-all text-muted-foreground">
                            {{ user.email }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                user.sso_linked
                                    ? 'Tertaut SSO'
                                    : 'Belum tertaut SSO'
                            }}
                            ·
                            {{
                                user.two_factor_enabled
                                    ? '2FA aktif'
                                    : '2FA belum aktif'
                            }}
                        </p>
                    </div>
                    <div v-if="user.can_update" class="flex flex-wrap gap-1">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="UserController.edit(user.id)"
                                >Ubah
                                <span class="sr-only">{{
                                    user.name
                                }}</span></Link
                            >
                        </Button>
                        <AccountStatusButton
                            v-if="!user.is_self"
                            :user-id="user.id"
                            :user-name="user.name"
                            :is-active="user.is_active"
                        />
                    </div>
                </li>
            </ul>

            <Pagination :paginator="users" />
        </template>
    </div>
</template>
