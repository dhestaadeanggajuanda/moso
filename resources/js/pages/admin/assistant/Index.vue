<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CircleCheck, CircleX, TriangleAlert } from '@lucide/vue';
import { ref, watch } from 'vue';
import AssistantController from '@/actions/App/Http/Controllers/Admin/AssistantController';
import ProfileForm from '@/Admin/Assistant/ProfileForm.vue';
import StyleNotesForm from '@/Admin/Assistant/StyleNotesForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatDateTime } from '@/lib/format';
import type {
    AssistantProfile,
    AssistantStats,
    AssistantStyleNotes,
} from '@/types/admin';

/**
 * Props tertunda (Inertia::defer): undefined selama dimuat, null bila
 * asisten AI tidak dapat dihubungi.
 */
const props = defineProps<{
    enabled: boolean;
    profile?: AssistantProfile | null;
    styleNotes?: AssistantStyleNotes | null;
    stats?: AssistantStats | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Asisten AI', href: AssistantController.index() },
        ],
    },
});

const tab = ref('profile');

// Data form diambil sekali setelah termuat. Props tertunda dimuat ulang setiap kali
// halaman dikunjungi (termasuk setelah simpan atau galat validasi); tanpa ini form
// akan dipasang ulang dan isian serta pesan galatnya hilang.
const profileData = ref(props.profile);
const styleData = ref(props.styleNotes);

watch(
    () => props.profile,
    (value) => {
        if (value !== undefined && !profileData.value) {
            profileData.value = value;
        }
    },
);

watch(
    () => props.styleNotes,
    (value) => {
        if (value !== undefined && !styleData.value) {
            styleData.value = value;
        }
    },
);
</script>

<template>
    <Head title="Asisten AI" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Asisten AI"
            description="Bahan yang dipakai asisten saat menyusun draf berita: profil dinas, gaya tulisan, dan berita yang sudah terbit. Hanya Super Admin yang dapat mengubah halaman ini."
        />

        <Alert v-if="!enabled">
            <TriangleAlert aria-hidden="true" />
            <AlertTitle>Asisten AI belum aktif</AlertTitle>
            <AlertDescription>
                Isi AGENTIC_ENABLED=true, AGENTIC_URL, dan AGENTIC_TOKEN di .env
                server, lalu muat ulang konfigurasi aplikasi.
            </AlertDescription>
        </Alert>

        <Tabs v-else v-model="tab" class="gap-4">
            <TabsList class="w-full sm:w-fit">
                <TabsTrigger value="profile">Profil dinas</TabsTrigger>
                <TabsTrigger value="style">Gaya tulisan</TabsTrigger>
                <TabsTrigger value="status">Status</TabsTrigger>
            </TabsList>

            <TabsContent value="profile">
                <div v-if="profileData === undefined" class="grid gap-3">
                    <Skeleton class="h-9 w-full" />
                    <Skeleton class="h-24 w-full" />
                    <Skeleton class="h-9 w-2/3" />
                </div>
                <Alert v-else-if="profileData === null" variant="destructive">
                    <TriangleAlert aria-hidden="true" />
                    <AlertDescription>
                        Profil tidak dapat dimuat dari asisten AI. Muat ulang
                        halaman beberapa saat lagi.
                    </AlertDescription>
                </Alert>
                <ProfileForm v-else :profile="profileData" />
            </TabsContent>

            <TabsContent value="style">
                <div v-if="styleData === undefined" class="grid gap-3">
                    <Skeleton class="h-16 w-full" />
                    <Skeleton class="h-16 w-full" />
                </div>
                <Alert v-else-if="styleData === null" variant="destructive">
                    <TriangleAlert aria-hidden="true" />
                    <AlertDescription>
                        Gaya tulisan tidak dapat dimuat dari asisten AI. Muat
                        ulang halaman beberapa saat lagi.
                    </AlertDescription>
                </Alert>
                <StyleNotesForm v-else :style-notes="styleData" />
            </TabsContent>

            <TabsContent value="status">
                <div v-if="stats === undefined" class="grid gap-3">
                    <Skeleton class="h-6 w-1/2" />
                    <Skeleton class="h-20 w-full" />
                </div>
                <div v-else class="grid gap-4">
                    <p
                        class="flex items-center gap-2 text-sm font-medium"
                        :class="stats ? 'text-foreground' : 'text-destructive'"
                    >
                        <CircleCheck
                            v-if="stats"
                            class="size-4 text-primary"
                            aria-hidden="true"
                        />
                        <CircleX v-else class="size-4" aria-hidden="true" />
                        {{
                            stats
                                ? 'Terhubung ke asisten AI'
                                : 'Tidak terhubung. Periksa AGENTIC_URL, AGENTIC_TOKEN, dan jaringan ke server agentic.'
                        }}
                    </p>

                    <dl v-if="stats" class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border p-4">
                            <dt class="text-sm text-muted-foreground">
                                Berita terindeks
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ stats.indexed_articles }}
                            </dd>
                        </div>
                        <div class="rounded-lg border p-4">
                            <dt class="text-sm text-muted-foreground">
                                Draf 30 hari terakhir
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ stats.drafts_30d }}
                            </dd>
                        </div>
                        <div class="rounded-lg border p-4">
                            <dt class="text-sm text-muted-foreground">
                                Draf terakhir
                            </dt>
                            <dd class="text-sm font-medium">
                                {{ formatDateTime(stats.last_draft_at) }}
                            </dd>
                        </div>
                    </dl>

                    <p class="text-sm text-muted-foreground">
                        Berita yang terbit, diubah, atau diarsipkan disalin ke
                        memori asisten lewat antrean. Untuk mengisi memori dari
                        berita lama, jalankan
                        <code class="rounded bg-muted px-1 py-0.5 text-xs"
                            >php artisan agentic:sync-articles</code
                        >
                        di server.
                    </p>
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
