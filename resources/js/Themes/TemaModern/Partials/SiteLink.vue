<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
/**
 * Tautan menu: tautan yang dibuka di tab baru diberi rel aman dan keterangan
 * untuk pembaca layar.
 */
defineProps<{
    href: string;
    newTab?: boolean;
    current?: boolean;
}>();
</script>

<template>
    <a
        :href="href"
        :target="newTab ? '_blank' : undefined"
        :rel="newTab ? 'noopener noreferrer' : undefined"
        :aria-current="current ? 'page' : undefined"
    >
        <slot />
        <span v-if="newTab" class="sr-only"> (membuka tab baru)</span>
    </a>
</template>
