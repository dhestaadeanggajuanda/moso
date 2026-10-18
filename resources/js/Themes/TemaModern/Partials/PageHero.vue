<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Kepala halaman daftar (layanan, berita, PPID, E-SAKIP): bidang navy dengan
 * remah roti, judul h1, dan deskripsi. Slot bawaan untuk kolom cari.
 */
defineProps<{
    homeUrl: string;
    title: string;
    description?: string | null;
    /** Remah roti setelah "Beranda"; item terakhir adalah halaman ini. */
    trail: { label: string; href?: string | null }[];
}>();
</script>

<template>
    <header class="bg-brand-900 relative isolate overflow-hidden text-white">
        <div
            class="mx-auto max-w-7xl px-4 pt-8 pb-12 sm:px-6 sm:pt-12 sm:pb-16 lg:px-8"
        >
            <nav aria-label="Remah roti" class="text-brand-200 text-sm">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <li>
                        <a
                            :href="homeUrl"
                            class="font-semibold text-white hover:underline"
                        >
                            Beranda
                        </a>
                    </li>
                    <template v-for="(crumb, index) in trail" :key="index">
                        <li aria-hidden="true">/</li>
                        <li
                            :aria-current="
                                index === trail.length - 1 ? 'page' : undefined
                            "
                        >
                            <a
                                v-if="crumb.href && index < trail.length - 1"
                                :href="crumb.href"
                                class="font-semibold text-white hover:underline"
                            >
                                {{ crumb.label }}
                            </a>
                            <template v-else>{{ crumb.label }}</template>
                        </li>
                    </template>
                </ol>
            </nav>
            <h1
                class="mt-4 text-3xl leading-tight font-extrabold tracking-tight sm:text-5xl"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="text-brand-100 mt-4 max-w-2xl text-lg leading-relaxed"
            >
                {{ description }}
            </p>

            <slot />
        </div>
    </header>
</template>
