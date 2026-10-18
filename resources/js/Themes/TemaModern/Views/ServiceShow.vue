<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan
    App\Http\Controllers\Site\ServiceController@show.
-->
<script setup lang="ts">
import {
    ArrowLeft,
    CircleCheck,
    ClipboardList,
    ExternalLink,
    ListOrdered,
    Megaphone,
    Package,
    Phone,
    Timer,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import type { PublicService, SiteContext } from '@/types/site';
import { formatDate } from '../Partials/format';
import SiteLink from '../Partials/SiteLink.vue';

const props = defineProps<{
    service: PublicService;
    indexUrl: string;
    site: SiteContext;
}>();

const phoneHref = computed(() =>
    props.site.contact.phone
        ? `tel:${props.site.contact.phone.replace(/[^\d+]/g, '')}`
        : null,
);
</script>

<template>
    <div>
        <header
            class="bg-brand-900 relative isolate overflow-hidden text-white"
        >
            <div
                class="mx-auto max-w-7xl px-4 pt-8 pb-12 sm:px-6 sm:pt-12 sm:pb-16 lg:px-8"
            >
                <nav aria-label="Remah roti" class="text-brand-200 text-sm">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <li>
                            <a
                                :href="site.links.home"
                                class="font-semibold text-white hover:underline"
                            >
                                Beranda
                            </a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li>
                            <a
                                :href="indexUrl"
                                class="font-semibold text-white hover:underline"
                            >
                                Layanan
                            </a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page" class="truncate">
                            {{ service.name }}
                        </li>
                    </ol>
                </nav>
                <h1
                    class="mt-4 max-w-4xl text-3xl leading-tight font-extrabold tracking-tight sm:text-[2.75rem]"
                >
                    {{ service.name }}
                </h1>
                <p
                    v-if="service.description"
                    class="text-brand-100 mt-4 max-w-3xl text-lg leading-relaxed"
                >
                    {{ service.description }}
                </p>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-12">
                <!-- Ringkasan tampil lebih dulu di ponsel, di samping kanan di desktop. -->
                <aside class="lg:order-last lg:col-span-4">
                    <div
                        class="rounded-card bg-canvas ring-line p-6 ring-1 shadow-card lg:sticky lg:top-6"
                    >
                        <h2 class="text-brand-950 text-lg font-bold">
                            Ringkasan layanan
                        </h2>
                        <dl class="mt-4 grid gap-4">
                            <div class="flex gap-3">
                                <Timer
                                    class="text-brand-600 mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-ink-muted text-sm">
                                        Jangka waktu
                                    </dt>
                                    <dd class="text-ink font-bold">
                                        {{ service.processing_time }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <CircleCheck
                                    v-if="service.is_free"
                                    class="text-ok mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <Wallet
                                    v-else
                                    class="text-brand-600 mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-ink-muted text-sm">
                                        Biaya
                                    </dt>
                                    <dd
                                        class="font-bold"
                                        :class="
                                            service.is_free
                                                ? 'text-ok'
                                                : 'text-ink'
                                        "
                                    >
                                        {{ service.cost }}
                                    </dd>
                                </div>
                            </div>
                            <div v-if="service.product" class="flex gap-3">
                                <Package
                                    class="text-brand-600 mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-ink-muted text-sm">
                                        Produk layanan
                                    </dt>
                                    <dd class="text-ink font-bold">
                                        {{ service.product }}
                                    </dd>
                                </div>
                            </div>
                        </dl>

                        <div class="border-line mt-6 border-t pt-5">
                            <h2
                                class="text-brand-950 flex items-center gap-2 font-bold"
                            >
                                <Megaphone
                                    class="text-brand-600 size-5"
                                    aria-hidden="true"
                                />
                                Pengaduan
                            </h2>
                            <p
                                v-if="service.complaint_channel"
                                class="text-ink-muted mt-2 text-[0.95rem] leading-relaxed"
                            >
                                {{ service.complaint_channel }}
                            </p>
                            <div class="mt-4 grid gap-2">
                                <SiteLink
                                    v-if="site.contact.complaint_url"
                                    :href="site.contact.complaint_url"
                                    new-tab
                                    class="bg-brand-700 hover:bg-brand-800 inline-flex h-11 items-center justify-center gap-2 rounded-full px-5 font-bold text-white transition-colors"
                                >
                                    Sampaikan pengaduan
                                    <ExternalLink
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                </SiteLink>
                                <a
                                    v-if="phoneHref"
                                    :href="phoneHref"
                                    class="text-brand-700 ring-line hover:ring-brand-300 inline-flex h-11 items-center justify-center gap-2 rounded-full px-5 font-bold ring-1"
                                >
                                    <Phone class="size-4" aria-hidden="true" />
                                    <span class="tabular-nums">{{
                                        site.contact.phone
                                    }}</span>
                                </a>
                            </div>
                        </div>

                        <p
                            v-if="service.updated_at"
                            class="text-ink-muted mt-6 text-sm"
                        >
                            Informasi diperbarui
                            <time :datetime="service.updated_at">
                                {{ formatDate(service.updated_at) }}
                            </time>
                        </p>
                    </div>
                </aside>

                <div class="grid content-start gap-12 lg:col-span-8">
                    <section aria-labelledby="persyaratan">
                        <h2
                            id="persyaratan"
                            class="text-brand-950 flex items-center gap-3 text-2xl font-extrabold tracking-tight"
                        >
                            <ClipboardList
                                class="text-brand-600 size-6"
                                aria-hidden="true"
                            />
                            Persyaratan
                        </h2>
                        <ul
                            v-if="service.requirements.length"
                            class="divide-line border-line mt-5 divide-y border-y"
                        >
                            <li
                                v-for="(
                                    requirement, index
                                ) in service.requirements"
                                :key="index"
                                class="flex gap-3 py-3.5 text-[1.0625rem] leading-relaxed"
                            >
                                <CircleCheck
                                    class="text-ok mt-1 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                {{ requirement }}
                            </li>
                        </ul>
                        <p v-else class="text-ink-muted mt-4">
                            Tidak ada persyaratan khusus.
                        </p>
                    </section>

                    <section
                        v-if="service.procedure.length"
                        aria-labelledby="prosedur"
                    >
                        <h2
                            id="prosedur"
                            class="text-brand-950 flex items-center gap-3 text-2xl font-extrabold tracking-tight"
                        >
                            <ListOrdered
                                class="text-brand-600 size-6"
                                aria-hidden="true"
                            />
                            Prosedur
                        </h2>
                        <ol class="mt-6">
                            <li
                                v-for="(step, index) in service.procedure"
                                :key="index"
                                class="relative grid grid-cols-[2.5rem_1fr] gap-4 pb-6 last:pb-0"
                            >
                                <span
                                    v-if="index < service.procedure.length - 1"
                                    class="bg-line absolute top-11 bottom-1 left-5 w-px"
                                    aria-hidden="true"
                                />
                                <span
                                    class="bg-brand-900 grid size-10 place-items-center rounded-full font-bold text-white tabular-nums"
                                    aria-hidden="true"
                                >
                                    {{ index + 1 }}
                                </span>
                                <p
                                    class="pt-1.5 text-[1.0625rem] leading-relaxed"
                                >
                                    <span class="sr-only"
                                        >Langkah {{ index + 1 }}:
                                    </span>
                                    {{ step }}
                                </p>
                            </li>
                        </ol>
                    </section>

                    <div class="border-line border-t pt-6">
                        <a
                            :href="indexUrl"
                            class="group text-brand-700 hover:text-brand-900 inline-flex min-h-11 items-center gap-2 font-bold"
                        >
                            <ArrowLeft
                                class="ease-out-expo size-4 transition-transform duration-300 group-hover:-translate-x-1"
                                aria-hidden="true"
                            />
                            Semua layanan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
