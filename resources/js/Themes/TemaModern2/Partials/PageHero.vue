<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Kepala halaman daftar (layanan, berita, PPID, E-SAKIP): remah roti di atas
 * pasir, lalu satu kartu putih berisi judul h1 dan deskripsi. Slot bawaan
 * untuk kolom cari.
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
    <header class="mx-auto max-w-7xl px-4 pt-4 pb-6 sm:px-6 sm:pb-8 lg:px-8">
        <nav aria-label="Remah roti" class="text-ink-muted text-sm">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <li>
                    <a
                        :href="homeUrl"
                        class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
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
                            class="text-action inline-flex min-h-11 items-center font-bold hover:underline"
                        >
                            {{ crumb.label }}
                        </a>
                        <template v-else>{{ crumb.label }}</template>
                    </li>
                </template>
            </ol>
        </nav>

        <div class="rounded-card bg-card p-5 shadow-card sm:p-8">
            <h1
                class="text-ink text-3xl leading-tight font-extrabold sm:text-4xl lg:text-5xl"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="text-ink-muted mt-3 max-w-2xl text-base leading-relaxed sm:text-lg"
            >
                {{ description }}
            </p>

            <slot />
        </div>
    </header>
</template>
