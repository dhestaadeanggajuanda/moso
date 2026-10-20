<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AccountStatusButton from '@/Admin/Users/AccountStatusButton.vue';
import SsoIdentityList from '@/Admin/Users/SsoIdentityList.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import type { Option, RoleOption, SsoIdentityRow } from '@/types/admin';

const props = defineProps<{
    account: {
        id: number;
        name: string;
        email: string;
        role: string | null;
        is_active: boolean;
        deactivated_at: string | null;
        two_factor_enabled: boolean;
        is_self: boolean;
        created_at: string | null;
        updated_at: string | null;
        sso_identities: SsoIdentityRow[];
    } | null;
    roles: RoleOption[];
    passwordSetups: Option[];
    sso: { enabled: boolean; label: string; local_login: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pengguna', href: UserController.index() }],
    },
});

const title = computed(() =>
    props.account ? 'Ubah pengguna' : 'Tambah pengguna',
);
const formAction = computed(() =>
    props.account
        ? UserController.update.form(props.account.id)
        : UserController.store.form(),
);
const formKey = computed(() =>
    props.account ? `${props.account.id}-${props.account.updated_at}` : 'new',
);

const passwordSetup = ref(props.passwordSetups[0]?.value ?? '');
const roleLocked = computed(() => props.account?.is_self === true);
</script>

<template>
    <Head :title="title" />

    <div class="flex max-w-3xl flex-1 flex-col gap-8 p-4 md:p-6">
        <PageHeader :title="title">
            <template v-if="account && !account.is_active" #actions>
                <Badge variant="outline">Nonaktif</Badge>
            </template>
        </PageHeader>

        <Form
            :key="formKey"
            v-bind="formAction"
            class="grid gap-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="name">Nama lengkap</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    maxlength="255"
                    autocomplete="off"
                    :default-value="account?.name"
                    :aria-invalid="!!errors.name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="email">Email dinas</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    required
                    maxlength="255"
                    autocomplete="off"
                    aria-describedby="email-hint"
                    :default-value="account?.email"
                    :aria-invalid="!!errors.email"
                />
                <p id="email-hint" class="text-sm text-muted-foreground">
                    Dipakai untuk login dan reset kata sandi<template
                        v-if="sso.enabled"
                        >, serta untuk menautkan akun {{ sso.label }}</template
                    >.
                </p>
                <InputError :message="errors.email" />
            </div>

            <fieldset class="grid gap-2">
                <legend class="mb-1 text-sm font-medium">Peran</legend>
                <template v-if="roleLocked">
                    <input type="hidden" name="role" :value="account?.role" />
                    <p class="text-sm">
                        <Badge variant="secondary">{{ account?.role }}</Badge>
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Anda tidak dapat mengubah peran akun Anda sendiri.
                    </p>
                </template>
                <template v-else>
                    <label
                        v-for="role in roles"
                        :key="role.value"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50"
                    >
                        <input
                            type="radio"
                            name="role"
                            required
                            class="mt-0.5 size-4 shrink-0 accent-primary focus-visible:outline-none"
                            :value="role.value"
                            :checked="
                                (account?.role ?? 'Kontributor') === role.value
                            "
                        />
                        <span class="grid gap-0.5">
                            <span class="text-sm font-medium">{{
                                role.label
                            }}</span>
                            <span class="text-sm text-muted-foreground">{{
                                role.description
                            }}</span>
                        </span>
                    </label>
                </template>
                <InputError :message="errors.role" />
            </fieldset>

            <fieldset v-if="!account" class="grid gap-2">
                <legend class="mb-1 text-sm font-medium">Akses awal</legend>
                <label
                    v-for="setup in passwordSetups"
                    :key="setup.value"
                    class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 has-checked:border-primary has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50"
                >
                    <input
                        v-model="passwordSetup"
                        type="radio"
                        name="password_setup"
                        required
                        class="size-4 shrink-0 accent-primary focus-visible:outline-none"
                        :value="setup.value"
                    />
                    <span class="text-sm">{{ setup.label }}</span>
                </label>
                <p
                    v-if="passwordSetup === 'invitation'"
                    class="text-sm text-muted-foreground"
                >
                    Pegawai menerima email berisi tautan untuk membuat kata
                    sandinya sendiri. Kata sandi tidak pernah dikirim lewat
                    email.
                </p>
                <p
                    v-else-if="passwordSetup === 'sso_only'"
                    class="text-sm text-muted-foreground"
                >
                    Pegawai masuk lewat {{ sso.label }} dengan email di atas.
                    Akun diberi kata sandi acak yang tidak diketahui siapa pun.
                </p>
                <InputError :message="errors.password_setup" />
            </fieldset>

            <div
                v-if="!account && passwordSetup === 'manual'"
                class="grid gap-5 sm:grid-cols-2"
            >
                <div class="grid gap-1.5">
                    <Label for="password">Kata sandi awal</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        :aria-invalid="!!errors.password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="password_confirmation">Ulangi kata sandi</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <p class="text-sm text-muted-foreground sm:col-span-2">
                    Sampaikan kata sandi secara langsung, bukan lewat chat atau
                    email, dan minta pegawai menggantinya setelah masuk.
                </p>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Simpan</Button>
                <Button variant="ghost" as-child>
                    <Link :href="UserController.index()">Batal</Link>
                </Button>
            </div>
        </Form>

        <template v-if="account">
            <section class="grid gap-3 border-t pt-6" aria-labelledby="status">
                <h2 id="status" class="font-semibold">Status akun</h2>
                <dl class="grid gap-2 text-sm sm:grid-cols-[10rem_1fr]">
                    <dt class="text-muted-foreground">Status</dt>
                    <dd>
                        {{
                            account.is_active
                                ? 'Aktif'
                                : `Nonaktif sejak ${formatDateTime(account.deactivated_at)}`
                        }}
                    </dd>
                    <dt class="text-muted-foreground">
                        Autentikasi dua faktor
                    </dt>
                    <dd>
                        {{
                            account.two_factor_enabled ? 'Aktif' : 'Belum aktif'
                        }}
                    </dd>
                    <dt class="text-muted-foreground">Dibuat</dt>
                    <dd>{{ formatDateTime(account.created_at) }}</dd>
                </dl>
                <div v-if="!account.is_self">
                    <AccountStatusButton
                        :user-id="account.id"
                        :user-name="account.name"
                        :is-active="account.is_active"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Anda tidak dapat menonaktifkan akun Anda sendiri.
                </p>
            </section>

            <section
                v-if="sso.enabled || account.sso_identities.length > 0"
                class="grid gap-3 border-t pt-6"
                aria-labelledby="sso"
            >
                <h2 id="sso" class="font-semibold">Login SSO</h2>
                <SsoIdentityList
                    :user-id="account.id"
                    :identities="account.sso_identities"
                    :sso-label="sso.label"
                />
            </section>
        </template>
    </div>
</template>
