<script setup lang="ts">
/**
 * Dialog "Tulis dengan AI" di form artikel.
 *
 * Bahan (judul, ringkasan, foto, info tambahan) dikirim ke moso, yang
 * meneruskannya ke asisten AI (moso-agentic). Penyusunan draf berjalan di
 * server agentic, jadi komponen ini hanya menanyakan status tiap 2 detik,
 * menampilkan tahapnya, lalu pratinjau dan daftar data yang perlu dikonfirmasi.
 * Hak akses dan sanitasi HTML dikerjakan server; tombol ini hanya tampilan.
 */
import { useHttp } from '@inertiajs/vue3';
import {
    Check,
    Circle,
    ImageIcon,
    LoaderCircle,
    Sparkles,
    TriangleAlert,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ArticleAiDraftController from '@/actions/App/Http/Controllers/Admin/ArticleAiDraftController';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { AiDraftApplication, AiDraftStatus, Option } from '@/types/admin';

const props = defineProps<{
    /** Artikel yang sedang diubah; null untuk artikel baru. */
    articleId: number | null;
    title: string;
    description: string;
    /** Foto yang baru dipilih di form (belum disimpan). */
    imageFile: File | null;
    /** Gambar artikel yang tersimpan dan akan tetap dipakai. */
    existingImageUrl: string | null;
    kinds: Option[];
    /** Isian form saat ini, untuk memutuskan apa yang perlu konfirmasi sebelum ditimpa. */
    current: {
        body: string;
        excerpt: string;
        imageAlt: string;
        hasCategories: boolean;
    };
}>();

const emit = defineEmits<{
    apply: [application: AiDraftApplication];
}>();

type Phase = 'input' | 'working' | 'result' | 'confirm' | 'failed';

/** Bagian respons HTTP yang dipakai untuk pesan galat (bentuk HttpResponse Inertia). */
type ErrorResponse = { status: number; data: unknown };

type DraftRequest = {
    kind: string;
    title: string;
    description: string;
    extra_facts: string;
    instruction: string;
    previous_draft_id: string | null;
    image: File | null;
    article_id: number | null;
    use_article_image: boolean;
};

const POLL_INTERVAL_MS = 2000;
const MAX_WAIT_MS = 4 * 60 * 1000;
const MAX_POLL_FAILURES = 3;
const UNAVAILABLE =
    'Asisten AI sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.';

const stages: { key: NonNullable<AiDraftStatus['stage']>; label: string }[] = [
    { key: 'observing', label: 'Membaca foto…' },
    { key: 'recalling', label: 'Mengingat berita sebelumnya…' },
    { key: 'writing', label: 'Menulis draf…' },
    { key: 'reviewing', label: 'Memeriksa fakta…' },
];

const open = ref(false);
const phase = ref<Phase>('input');
const kind = ref(props.kinds[0]?.value ?? 'berita');
const extraFacts = ref('');
const instruction = ref('');
const showRewrite = ref(false);
const draft = ref<AiDraftStatus | null>(null);
const errorMessage = ref<string | null>(null);
const useTitleSuggestion = ref(false);
const replaceExcerpt = ref(false);
const replaceImageAlt = ref(false);

const request = useHttp<DraftRequest, AiDraftStatus>({
    kind: 'berita',
    title: '',
    description: '',
    extra_facts: '',
    instruction: '',
    previous_draft_id: null,
    image: null,
    article_id: null,
    use_article_image: true,
});
const statusRequest = useHttp<Record<string, string>, AiDraftStatus>({});

let pollTimer: ReturnType<typeof setTimeout> | null = null;
let pollFailures = 0;
let startedAt = 0;

const result = computed(() => draft.value?.result ?? null);
const hasPhoto = computed(
    () => props.imageFile !== null || props.existingImageUrl !== null,
);
const titleMissing = computed(() => props.title.trim() === '');
const activeStageIndex = computed(() =>
    stages.findIndex((stage) => stage.key === draft.value?.stage),
);
const statusLabel = computed(() => {
    const stage = stages[activeStageIndex.value];

    if (stage) {
        return stage.label;
    }

    return draft.value?.status === 'queued'
        ? 'Menunggu giliran…'
        : 'Menyiapkan…';
});
const titleSuggestionDiffers = computed(
    () =>
        !!result.value?.title_suggestion &&
        result.value.title_suggestion.trim() !== props.title.trim(),
);
const overwrites = computed(() => ({
    body: props.current.body.trim() !== '',
    excerpt: !!result.value?.excerpt && props.current.excerpt.trim() !== '',
    imageAlt:
        hasPhoto.value &&
        !!result.value?.image_alt &&
        props.current.imageAlt.trim() !== '',
}));

watch(open, (isOpen) => {
    if (isOpen) {
        phase.value = 'input';
        draft.value = null;
        errorMessage.value = null;
        instruction.value = '';
        showRewrite.value = false;
        request.clearErrors();
    } else {
        stopPolling();
    }
});

onBeforeUnmount(stopPolling);

function generate(rewrite = false): void {
    errorMessage.value = null;

    if (phase.value === 'failed') {
        phase.value = 'input';
    }

    request.kind = kind.value;
    request.title = props.title.trim();
    request.description = props.description.trim();
    request.extra_facts = extraFacts.value.trim();
    request.instruction = rewrite ? instruction.value.trim() : '';
    request.previous_draft_id = rewrite ? (draft.value?.id ?? null) : null;
    request.image = props.imageFile;
    request.article_id = props.articleId;
    request.use_article_image = props.existingImageUrl !== null;

    request
        .post(ArticleAiDraftController.store.url(), {
            onSuccess: (response) => {
                // Dialog sudah ditutup sebelum jawaban tiba: jangan mulai polling di latar belakang.
                if (!open.value) {
                    return;
                }

                draft.value = response;
                phase.value = 'working';
                showRewrite.value = false;
                pollFailures = 0;
                startedAt = Date.now();
                schedulePoll();
            },
            onHttpException: (response) => {
                errorMessage.value = serverMessage(response);
            },
            onNetworkError: () => {
                errorMessage.value =
                    'Koneksi terputus. Periksa jaringan lalu coba lagi.';
            },
        })
        .catch(() => {
            // Galat validasi ada di request.errors; galat lain di errorMessage.
        });
}

function schedulePoll(): void {
    pollTimer = setTimeout(poll, POLL_INTERVAL_MS);
}

function poll(): void {
    pollTimer = null;
    const current = draft.value;

    if (!current || phase.value !== 'working') {
        return;
    }

    if (Date.now() - startedAt > MAX_WAIT_MS) {
        fail('Asisten AI terlalu lama menjawab. Coba lagi nanti.');

        return;
    }

    statusRequest
        .get(ArticleAiDraftController.show.url(current.id), {
            onSuccess: (response) => {
                if (phase.value !== 'working') {
                    return;
                }

                pollFailures = 0;
                draft.value = response;

                if (response.status === 'done' && response.result) {
                    useTitleSuggestion.value = false;
                    phase.value = 'result';
                } else if (
                    response.status === 'failed' ||
                    response.status === 'done'
                ) {
                    fail(
                        response.error ??
                            'Asisten AI gagal menyusun draf. Coba tulis ulang.',
                    );
                } else {
                    schedulePoll();
                }
            },
        })
        .catch(() => {
            if (phase.value !== 'working') {
                return;
            }

            pollFailures++;

            if (pollFailures >= MAX_POLL_FAILURES) {
                fail(UNAVAILABLE);
            } else {
                schedulePoll();
            }
        });
}

function stopPolling(): void {
    if (pollTimer !== null) {
        clearTimeout(pollTimer);
        pollTimer = null;
    }

    if (phase.value === 'working') {
        phase.value = 'input';
    }

    statusRequest.cancel();
}

function fail(message: string): void {
    errorMessage.value = message;
    phase.value = 'failed';
}

function serverMessage(response: ErrorResponse): string {
    if (response.status === 429) {
        return 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.';
    }

    try {
        const body = JSON.parse(String(response.data)) as {
            message?: unknown;
        };

        return response.status === 503 && typeof body.message === 'string'
            ? body.message
            : UNAVAILABLE;
    } catch {
        return UNAVAILABLE;
    }
}

function useDraft(): void {
    const { body, excerpt, imageAlt } = overwrites.value;

    if (body || excerpt || imageAlt) {
        replaceExcerpt.value = false;
        replaceImageAlt.value = false;
        phase.value = 'confirm';

        return;
    }

    apply();
}

function apply(): void {
    const current = draft.value;
    const draftResult = result.value;

    if (!current || !draftResult) {
        return;
    }

    const fillExcerpt =
        !!draftResult.excerpt &&
        (props.current.excerpt.trim() === '' || replaceExcerpt.value);
    const fillImageAlt =
        hasPhoto.value &&
        !!draftResult.image_alt &&
        (props.current.imageAlt.trim() === '' || replaceImageAlt.value);

    emit('apply', {
        draftId: current.id,
        body: draftResult.body_html,
        excerpt: fillExcerpt ? (draftResult.excerpt ?? undefined) : undefined,
        imageAlt: fillImageAlt
            ? (draftResult.image_alt ?? undefined)
            : undefined,
        title:
            useTitleSuggestion.value && draftResult.title_suggestion
                ? draftResult.title_suggestion
                : undefined,
        categoryIds: props.current.hasCategories
            ? []
            : draftResult.category_ids,
    });

    open.value = false;
}
</script>

<template>
    <div>
        <Dialog v-model:open="open">
            <DialogTrigger as-child>
                <Button type="button" variant="outline" size="sm">
                    <Sparkles aria-hidden="true" />
                    Tulis dengan AI
                </Button>
            </DialogTrigger>

            <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Tulis dengan AI</DialogTitle>
                    <DialogDescription>
                        Asisten menyusun draf dari bahan di bawah. Periksa
                        hasilnya sebelum disimpan: data yang belum pasti
                        ditandai "Perlu dikonfirmasi".
                    </DialogDescription>
                </DialogHeader>

                <!-- Bahan -->
                <form
                    v-if="phase === 'input' || phase === 'failed'"
                    class="grid gap-4"
                    @submit.prevent="generate(false)"
                >
                    <Alert v-if="phase === 'failed'" variant="destructive">
                        <TriangleAlert aria-hidden="true" />
                        <AlertTitle>Draf belum jadi</AlertTitle>
                        <AlertDescription>{{ errorMessage }}</AlertDescription>
                    </Alert>

                    <dl
                        class="grid gap-3 rounded-md border bg-muted/40 p-3 text-sm"
                    >
                        <div class="grid gap-0.5">
                            <dt class="font-medium">Judul</dt>
                            <dd
                                :class="
                                    titleMissing
                                        ? 'text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    titleMissing
                                        ? 'Isi judul artikel dulu.'
                                        : title
                                }}
                            </dd>
                            <InputError :message="request.errors.title" />
                        </div>
                        <div class="grid gap-0.5">
                            <dt class="font-medium">Ringkasan</dt>
                            <dd class="text-muted-foreground">
                                {{
                                    description.trim() ||
                                    'Kosong. Tidak apa-apa, tetapi draf akan lebih tepat bila diisi satu-dua kalimat.'
                                }}
                            </dd>
                            <InputError :message="request.errors.description" />
                        </div>
                        <div class="grid gap-1">
                            <dt class="font-medium">Foto</dt>
                            <dd
                                class="flex items-center gap-3 text-muted-foreground"
                            >
                                <img
                                    v-if="!imageFile && existingImageUrl"
                                    :src="existingImageUrl"
                                    alt=""
                                    class="aspect-video w-24 rounded border object-cover"
                                />
                                <ImageIcon
                                    v-else
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="min-w-0 break-words">
                                    {{
                                        imageFile
                                            ? imageFile.name
                                            : existingImageUrl
                                              ? 'Gambar utama artikel saat ini'
                                              : 'Tanpa foto. Pilih gambar utama di form agar asisten bisa membacanya.'
                                    }}
                                </span>
                            </dd>
                            <InputError :message="request.errors.image" />
                        </div>
                    </dl>

                    <div class="grid gap-1.5">
                        <Label for="ai-kind">Jenis tulisan</Label>
                        <NativeSelect id="ai-kind" v-model="kind">
                            <option
                                v-for="option in kinds"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </NativeSelect>
                        <InputError :message="request.errors.kind" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="ai-extra-facts">
                            Info tambahan (opsional)
                        </Label>
                        <Textarea
                            id="ai-extra-facts"
                            v-model="extraFacts"
                            rows="3"
                            maxlength="1000"
                            placeholder="Tanggal, tempat, narasumber, jumlah peserta, atau kutipan yang benar-benar diucapkan."
                            aria-describedby="ai-extra-facts-help"
                            :aria-invalid="!!request.errors.extra_facts"
                        />
                        <p
                            id="ai-extra-facts-help"
                            class="text-xs text-muted-foreground"
                        >
                            Asisten tidak mengarang fakta. Data yang tidak ada
                            di sini akan ditandai untuk Anda lengkapi.
                        </p>
                        <InputError :message="request.errors.extra_facts" />
                    </div>

                    <Alert
                        v-if="errorMessage && phase === 'input'"
                        variant="destructive"
                    >
                        <TriangleAlert aria-hidden="true" />
                        <AlertDescription>{{ errorMessage }}</AlertDescription>
                    </Alert>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="titleMissing || request.processing"
                        >
                            <LoaderCircle
                                v-if="request.processing"
                                class="animate-spin"
                                aria-hidden="true"
                            />
                            <Sparkles v-else aria-hidden="true" />
                            {{
                                phase === 'failed' ? 'Coba lagi' : 'Tulis draf'
                            }}
                        </Button>
                    </DialogFooter>
                </form>

                <!-- Progres -->
                <div v-else-if="phase === 'working'" class="grid gap-4">
                    <p class="sr-only" role="status" aria-live="polite">
                        {{ statusLabel }}
                    </p>
                    <ol class="grid gap-2 text-sm">
                        <li
                            v-for="(stage, index) in stages"
                            :key="stage.key"
                            class="flex min-h-6 items-center gap-2"
                            :class="
                                index === activeStageIndex
                                    ? 'font-medium text-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            <Check
                                v-if="index < activeStageIndex"
                                class="size-4 text-primary"
                                aria-hidden="true"
                            />
                            <LoaderCircle
                                v-else-if="index === activeStageIndex"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            <Circle
                                v-else
                                class="size-4 opacity-40"
                                aria-hidden="true"
                            />
                            {{ stage.label }}
                        </li>
                    </ol>
                    <p
                        v-if="activeStageIndex === -1"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <LoaderCircle
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        {{ statusLabel }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Biasanya kurang dari satu menit. Anda boleh menutup
                        dialog ini; draf yang sedang disusun akan dibatalkan
                        dari tampilan.
                    </p>
                </div>

                <!-- Hasil -->
                <div
                    v-else-if="phase === 'result' && result"
                    class="grid gap-4"
                >
                    <p class="sr-only" role="status" aria-live="polite">
                        Draf selesai.
                    </p>

                    <Alert v-if="result.needs_confirmation.length">
                        <TriangleAlert aria-hidden="true" />
                        <AlertTitle>
                            Perlu dikonfirmasi ({{
                                result.needs_confirmation.length
                            }})
                        </AlertTitle>
                        <AlertDescription>
                            <ul class="grid list-disc gap-1 pl-5">
                                <li
                                    v-for="(
                                        entry, index
                                    ) in result.needs_confirmation"
                                    :key="index"
                                >
                                    <span class="font-medium text-foreground">
                                        {{ entry.item }}
                                    </span>
                                    <template v-if="entry.question">
                                        : {{ entry.question }}
                                    </template>
                                </li>
                            </ul>
                        </AlertDescription>
                    </Alert>

                    <div class="grid gap-1 text-sm">
                        <p
                            v-if="result.title_suggestion"
                            class="text-muted-foreground"
                        >
                            Saran judul:
                            <span class="text-foreground">
                                {{ result.title_suggestion }}
                            </span>
                        </p>
                        <p v-if="result.excerpt" class="text-muted-foreground">
                            Ringkasan:
                            <span class="text-foreground">
                                {{ result.excerpt }}
                            </span>
                        </p>
                        <p class="text-xs text-muted-foreground">
                            ±{{ result.word_count }} kata
                        </p>
                    </div>

                    <!-- body_html sudah disanitasi server (HtmlSanitizer), aman untuk v-html. -->
                    <div
                        class="max-h-80 space-y-3 overflow-y-auto rounded-md border p-4 text-sm leading-relaxed [&_a]:text-primary [&_a]:underline [&_h2]:text-base [&_h2]:font-semibold [&_h3]:font-semibold [&_mark]:rounded-sm [&_mark]:px-0.5 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6"
                        tabindex="0"
                        aria-label="Pratinjau draf"
                        v-html="result.body_html"
                    />

                    <label
                        v-if="titleSuggestionDiffers"
                        class="flex min-h-6 items-start gap-2 text-sm"
                    >
                        <input
                            v-model="useTitleSuggestion"
                            type="checkbox"
                            class="mt-0.5 size-4 accent-primary"
                        />
                        Ganti judul dengan saran asisten
                    </label>

                    <div v-if="showRewrite" class="grid gap-1.5">
                        <Label for="ai-instruction">
                            Apa yang perlu diubah?
                        </Label>
                        <Textarea
                            id="ai-instruction"
                            v-model="instruction"
                            rows="2"
                            maxlength="300"
                            placeholder="mis. Lebih singkat, tekankan jadwal layanan."
                            :aria-invalid="!!request.errors.instruction"
                        />
                        <InputError :message="request.errors.instruction" />
                    </div>

                    <Alert v-if="errorMessage" variant="destructive">
                        <TriangleAlert aria-hidden="true" />
                        <AlertDescription>{{ errorMessage }}</AlertDescription>
                    </Alert>

                    <DialogFooter class="gap-2">
                        <Button
                            v-if="!showRewrite"
                            type="button"
                            variant="outline"
                            @click="showRewrite = true"
                        >
                            Tulis ulang
                        </Button>
                        <Button
                            v-else
                            type="button"
                            variant="outline"
                            :disabled="request.processing"
                            @click="generate(true)"
                        >
                            {{
                                request.processing
                                    ? 'Mengirim…'
                                    : 'Tulis ulang dengan instruksi'
                            }}
                        </Button>
                        <Button type="button" @click="useDraft">
                            Pakai draf ini
                        </Button>
                    </DialogFooter>
                </div>

                <!-- Konfirmasi sebelum menimpa isian -->
                <div v-else-if="phase === 'confirm'" class="grid gap-4">
                    <p class="text-sm">
                        Beberapa isian form sudah terisi. Pilih yang ingin
                        diganti dengan hasil asisten.
                    </p>
                    <ul class="grid gap-2 text-sm">
                        <li v-if="overwrites.body" class="flex gap-2">
                            <TriangleAlert
                                class="mt-0.5 size-4 shrink-0 text-destructive"
                                aria-hidden="true"
                            />
                            Isi artikel saat ini akan diganti seluruhnya.
                        </li>
                        <li v-if="overwrites.excerpt">
                            <label class="flex min-h-6 items-start gap-2">
                                <input
                                    v-model="replaceExcerpt"
                                    type="checkbox"
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                Ganti juga ringkasan yang sudah ada
                            </label>
                        </li>
                        <li v-if="overwrites.imageAlt">
                            <label class="flex min-h-6 items-start gap-2">
                                <input
                                    v-model="replaceImageAlt"
                                    type="checkbox"
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                Ganti juga teks alternatif gambar
                            </label>
                        </li>
                    </ul>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="phase = 'result'"
                        >
                            Kembali
                        </Button>
                        <Button type="button" @click="apply">
                            Ya, pakai draf
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
