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
            class="mx-auto max-w-7xl px-4 pt-4 pb-6 sm:px-6 sm:pb-8 lg:px-8"
        >
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
                            :href="indexUrl"
                            class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
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
            <div class="rounded-card bg-card p-5 shadow-card sm:p-8">
                <h1
                    class="text-ink max-w-4xl text-3xl leading-tight font-extrabold sm:text-4xl lg:text-5xl"
                >
                    {{ service.name }}
                </h1>
                <p
                    v-if="service.description"
                    class="text-ink-muted mt-3 max-w-3xl text-base leading-relaxed sm:text-lg"
                >
                    {{ service.description }}
                </p>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-12 lg:gap-8">
                <!-- Ringkasan tampil lebih dulu di ponsel, di samping kanan di desktop. -->
                <aside class="lg:order-last lg:col-span-4">
                    <div
                        class="rounded-card bg-card p-5 shadow-card sm:p-6 lg:sticky lg:top-6"
                    >
                        <h2 class="text-ink text-lg font-extrabold">
                            Ringkasan layanan
                        </h2>
                        <dl class="mt-4 grid gap-4">
                            <div class="flex gap-3">
                                <Timer
                                    class="text-ink-muted mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-ink-muted text-sm">
                                        Jangka waktu
                                    </dt>
                                    <dd class="text-ink font-extrabold">
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
                                    class="text-ink-muted mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-ink-muted text-sm">
                                        Biaya
                                    </dt>
                                    <dd
                                        class="font-extrabold"
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
                                    class="text-ink-muted mt-0.5 size-5 flex-none"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-ink-muted text-sm">
                                        Produk layanan
                                    </dt>
                                    <dd class="text-ink font-extrabold">
                                        {{ service.product }}
                                    </dd>
                                </div>
                            </div>
                        </dl>

                        <div class="border-line mt-6 border-t pt-5">
                            <h2
                                class="text-ink flex items-center gap-2 font-extrabold"
                            >
                                <Megaphone
                                    class="text-ink-muted size-5"
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
                                    class="btn-action press"
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
                                    class="btn-quiet press bg-ground h-12"
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

                <div class="grid content-start gap-4 lg:col-span-8 lg:gap-6">
                    <section
                        aria-labelledby="persyaratan"
                        class="rounded-card bg-card p-5 shadow-card sm:p-8"
                    >
                        <h2
                            id="persyaratan"
                            class="text-ink flex items-center gap-3 text-xl font-extrabold sm:text-2xl"
                        >
                            <span
                                class="bg-ground-deep text-ink rounded-icon grid size-10 flex-none place-items-center"
                                aria-hidden="true"
                            >
                                <ClipboardList class="size-5" />
                            </span>
                            Persyaratan
                        </h2>
                        <ul
                            v-if="service.requirements.length"
                            class="divide-line mt-4 divide-y"
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
                        class="rounded-card bg-card p-5 shadow-card sm:p-8"
                    >
                        <h2
                            id="prosedur"
                            class="text-ink flex items-center gap-3 text-xl font-extrabold sm:text-2xl"
                        >
                            <span
                                class="bg-ground-deep text-ink rounded-icon grid size-10 flex-none place-items-center"
                                aria-hidden="true"
                            >
                                <ListOrdered class="size-5" />
                            </span>
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
                                    class="bg-ground-deep absolute top-11 bottom-1 left-[1.1875rem] w-0.5"
                                    aria-hidden="true"
                                />
                                <span
                                    class="bg-ink grid size-10 place-items-center rounded-full font-extrabold text-card tabular-nums"
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

                    <div>
                        <a :href="indexUrl" class="group btn-quiet press">
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
