<script setup lang="ts">
/**
 * Pengganti blok yang belum didukung tema atau gagal dirender.
 * Di produksi tidak menampilkan apa pun agar pengunjung tidak melihat pesan teknis.
 */
defineProps<{
    type: string;
    reason?: 'missing' | 'error';
}>();

const isDevelopment = import.meta.env.DEV;
</script>

<template>
    <div
        v-if="isDevelopment"
        class="mx-auto my-4 max-w-5xl rounded border-2 border-dashed border-amber-500 p-4 text-sm text-amber-800"
        role="note"
    >
        {{
            reason === 'error'
                ? `Blok "${type}" gagal dirender. Lihat console browser.`
                : `Blok "${type}" belum tersedia di tema ini.`
        }}
    </div>
</template>
