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
        <header class="mx-auto max-w-4xl px-4 pt-4 sm:px-6 lg:px-8">
            <nav aria-label="Remah roti" class="text-ink-muted text-sm">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <li>
                        <a
                            :href="site.links.home"
                            class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
                        >
                            Beranda
                        </a>
                    </li>
                    <li aria-hidden="true">/</li>
                    <li>
                        <a
                            :href="site.links.news"
                            class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
                        >
                            Berita
                        </a>
                    </li>
                </ol>
            </nav>

            <h1
                class="text-ink mt-1 text-3xl leading-[1.15] font-extrabold sm:text-4xl lg:text-5xl"
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
                class="text-ink-muted mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm"
            >
                <SourceBadge source="local" />
                <time
                    v-if="article.published_at"
                    :datetime="article.published_at"
                    class="text-ink font-bold"
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
                    class="text-ink shadow-chip rounded-full bg-card px-3 py-1 text-sm font-bold"
                >
                    {{ category.name }}
                </li>
            </ul>
        </header>

        <figure
            v-if="article.image_url"
            class="mx-auto mt-6 max-w-5xl px-4 sm:mt-8 sm:px-6 lg:px-8"
        >
            <img
                :src="article.image_url"
                :alt="article.image_alt ?? ''"
                fetchpriority="high"
                decoding="async"
                class="rounded-card bg-ground-deep aspect-[16/9] w-full object-cover"
            />
        </figure>

        <div class="mx-auto max-w-4xl px-4 pt-6 sm:px-6 sm:pt-8 lg:px-8">
            <div class="rounded-card bg-card p-5 shadow-card sm:p-10">
                <!-- Isi artikel sudah disanitasi server (HtmlSanitizer) sebelum disimpan. -->
                <div class="prose-moso" v-html="article.body" />
            </div>

            <div class="mt-6">
                <a :href="site.links.news" class="group btn-quiet press">
                    <ArrowLeft
                        class="ease-out-expo size-4 transition-transform duration-300 group-hover:-translate-x-1"
                        aria-hidden="true"
                    />
                    Berita lainnya
                </a>
            </div>
        </div>
    </article>
</template>
