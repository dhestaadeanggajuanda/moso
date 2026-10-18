<script setup lang="ts">
/**
 * Mengisolasi error per blok: blok yang gagal tidak merusak halaman (OWASP A10).
 */
import { onErrorCaptured, ref } from 'vue';
import FallbackBlock from '@/Core/FallbackBlock.vue';

const props = defineProps<{
    type: string;
}>();

const failed = ref(false);

onErrorCaptured((error) => {
    failed.value = true;
    console.error(`[PageRenderer] Blok "${props.type}" gagal dirender.`, error);

    // Hentikan propagasi agar halaman tetap tampil.
    return false;
});
</script>

<template>
    <slot v-if="!failed" />
    <FallbackBlock v-else :type="type" reason="error" />
</template>
