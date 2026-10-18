<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\ArticleController.
-->
<script setup lang="ts">
import { ArrowLeft, UserRound } from '@lucide/vue';
import type { PublicArticle, SiteContext } from '@/types/site';
import { formatDate } from '../Partials/format';
import SourceBadge from '../Partials/SourceBadge.vue';

defineProps<{
    article: PublicArticle;
    site: SiteContext;
}>();
</script>

<template>
    <article>
        <header class="border-line bg-mist border-b">
            <div
                class="mx-auto max-w-4xl px-4 pt-8 pb-10 sm:px-6 sm:pt-12 sm:pb-14 lg:px-8"
            >
                <nav aria-label="Remah roti" class="text-ink-muted text-sm">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <li>
                            <a
                                :href="site.links.home"
                                class="text-brand-700 font-semibold hover:underline"
                            >
                                Beranda
                            </a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li>Berita</li>
                    </ol>
                </nav>

                <h1
                    class="text-brand-950 mt-5 text-3xl leading-[1.15] font-extrabold tracking-tight sm:text-4xl lg:text-[2.75rem]"
                >
                    {{ article.title }}
                </h1>

                <p
                    v-if="article.excerpt"
                    class="text-ink-muted mt-4 max-w-3xl text-lg leading-relaxed sm:text-xl"
                >
                    {{ article.excerpt }}
                </p>

                <div
                    class="text-ink-muted mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm"
                >
                    <SourceBadge source="local" />
                    <time
                        v-if="article.published_at"
                        :datetime="article.published_at"
                        class="text-ink font-semibold"
                    >
                        {{ formatDate(article.published_at) }}
                    </time>
                    <span class="inline-flex items-center gap-1.5">
                        <UserRound class="size-4" aria-hidden="true" />
                        <span class="sr-only">Penulis: </span>
                        {{ article.author }}
                    </span>
                </div>

                <ul
                    v-if="article.categories.length"
                    class="mt-4 flex flex-wrap gap-2"
                    aria-label="Kategori"
                >
                    <li
                        v-for="category in article.categories"
                        :key="category.slug"
                        class="bg-canvas text-brand-800 ring-line rounded-full px-3 py-1 text-sm font-semibold ring-1"
                    >
                        {{ category.name }}
                    </li>
                </ul>
            </div>
        </header>

        <figure
            v-if="article.image_url"
            class="mx-auto mt-8 max-w-5xl px-4 sm:mt-10 sm:px-6 lg:px-8"
        >
            <img
                :src="article.image_url"
                :alt="article.image_alt ?? ''"
                fetchpriority="high"
                decoding="async"
                class="rounded-card bg-brand-50 aspect-[16/9] w-full object-cover shadow-card"
            />
        </figure>

        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <!-- Isi artikel sudah disanitasi server (HtmlSanitizer) sebelum disimpan. -->
            <div class="prose-moso" v-html="article.body" />

            <div class="border-line mt-12 border-t pt-6">
                <a
                    :href="site.links.home"
                    class="group text-brand-700 hover:text-brand-900 inline-flex min-h-11 items-center gap-2 font-bold"
                >
                    <ArrowLeft
                        class="ease-out-expo size-4 transition-transform duration-300 group-hover:-translate-x-1"
                        aria-hidden="true"
                    />
                    Kembali ke beranda
                </a>
            </div>
        </div>
    </article>
</template>
