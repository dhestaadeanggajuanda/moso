<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil } from '@lucide/vue';
import ArticleCommentController from '@/actions/App/Http/Controllers/Admin/ArticleCommentController';
import ArticleController from '@/actions/App/Http/Controllers/Admin/ArticleController';
import ActionButtons from '@/Admin/Workflow/ActionButtons.vue';
import ArticleHistory from '@/Admin/Workflow/ArticleHistory.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import type {
    ArticleHistoryEntry,
    StatusValue,
    WorkflowAction,
} from '@/types/admin';

type ArticleDetail = {
    id: number;
    title: string;
    excerpt: string | null;
    /** HTML yang sudah disanitasi server. */
    body: string;
    image_url: string | null;
    image_alt: string | null;
    status: StatusValue;
    author: string;
    categories: string[];
    published_at: string | null;
    updated_at: string | null;
    public_url: string | null;
};

defineProps<{
    article: ArticleDetail;
    history: ArticleHistoryEntry[];
    actions: WorkflowAction[];
    can: { update: boolean; delete: boolean; comment: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Artikel', href: ArticleController.index() }],
    },
});
</script>

<template>
    <Head :title="`Tinjau: ${article.title}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="article.title"
            :description="`Oleh ${article.author} · diperbarui ${formatDateTime(article.updated_at)}`"
        >
            <template #actions>
                <StatusBadge :status="article.status" />
                <Button v-if="can.update" variant="outline" size="sm" as-child>
                    <Link :href="ArticleController.edit(article.id)">
                        <Pencil class="size-4" aria-hidden="true" />
                        Ubah isi
                    </Link>
                </Button>
                <Button
                    v-if="article.public_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <a
                        :href="article.public_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink class="size-4" aria-hidden="true" />
                        Lihat di situs
                        <span class="sr-only">(membuka tab baru)</span>
                    </a>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <!-- Di ponsel tindakan tampil lebih dulu; di desktop di kolom kanan. -->
            <aside class="grid content-start gap-6 lg:order-last">
                <section
                    aria-labelledby="tindakan-heading"
                    class="grid gap-3 rounded-lg border p-4"
                >
                    <h2 id="tindakan-heading" class="text-sm font-semibold">
                        Tindakan
                    </h2>
                    <ActionButtons
                        v-if="actions.length"
                        :article-id="article.id"
                        :article-title="article.title"
                        :actions="actions"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        Tidak ada tindakan untuk Anda pada status
                        {{ article.status.label.toLowerCase() }}.
                    </p>
                </section>

                <section
                    aria-labelledby="info-heading"
                    class="grid gap-3 rounded-lg border p-4 text-sm"
                >
                    <h2 id="info-heading" class="font-semibold">Informasi</h2>
                    <dl class="grid grid-cols-[7rem_1fr] gap-x-3 gap-y-2">
                        <dt class="text-muted-foreground">Penulis</dt>
                        <dd>{{ article.author }}</dd>
                        <dt class="text-muted-foreground">Kategori</dt>
                        <dd>
                            {{
                                article.categories.length
                                    ? article.categories.join(', ')
                                    : '–'
                            }}
                        </dd>
                        <dt class="text-muted-foreground">Terbit</dt>
                        <dd>
                            {{
                                article.published_at
                                    ? formatDateTime(article.published_at)
                                    : 'Belum terbit'
                            }}
                        </dd>
                    </dl>
                </section>

                <section
                    aria-labelledby="riwayat-heading"
                    class="grid gap-4 rounded-lg border p-4"
                >
                    <h2 id="riwayat-heading" class="text-sm font-semibold">
                        Riwayat dan diskusi
                    </h2>
                    <ArticleHistory :entries="history" />

                    <Form
                        v-if="can.comment"
                        v-bind="ArticleCommentController.store.form(article.id)"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        class="grid gap-2 border-t pt-4"
                        v-slot="{ errors, processing }"
                    >
                        <Label for="comment-body">Tulis komentar</Label>
                        <Textarea
                            id="comment-body"
                            name="body"
                            rows="3"
                            maxlength="2000"
                            required
                            :aria-invalid="!!errors.body"
                        />
                        <InputError :message="errors.body" />
                        <Button
                            type="submit"
                            variant="secondary"
                            size="sm"
                            class="justify-self-start"
                            :disabled="processing"
                        >
                            Kirim komentar
                        </Button>
                    </Form>
                </section>
            </aside>

            <article
                aria-labelledby="pratinjau-heading"
                class="grid content-start gap-4 rounded-lg border p-4 md:p-6"
            >
                <h2
                    id="pratinjau-heading"
                    class="text-sm font-semibold text-muted-foreground"
                >
                    Pratinjau isi
                </h2>
                <img
                    v-if="article.image_url"
                    :src="article.image_url"
                    :alt="article.image_alt ?? ''"
                    class="aspect-video w-full rounded-md object-cover"
                    loading="lazy"
                />
                <p v-if="article.excerpt" class="text-lg text-muted-foreground">
                    {{ article.excerpt }}
                </p>
                <!-- Isi artikel sudah disanitasi server (HtmlSanitizer) sebelum disimpan. -->
                <div
                    class="max-w-none space-y-4 leading-relaxed [&_a]:text-primary [&_a]:underline [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg [&_h3]:font-semibold [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6"
                    v-html="article.body"
                />
            </article>
        </div>
    </div>
</template>
