<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props adalah kontrak dengan BlockRegistry
    (App\Services\PageBuilder\Blocks\RichTextBlock).
-->
<script setup lang="ts">
import type { BlockDataMap, RichTextProps } from '@/types/blocks';

defineProps<
    RichTextProps & {
        data: BlockDataMap['RichText'];
    }
>();
</script>

<template>
    <section class="px-4 pt-8 sm:px-6 sm:pt-14 lg:px-8">
        <div
            class="rounded-card mx-auto max-w-4xl bg-card p-5 shadow-card sm:p-10"
        >
            <!-- HTML sudah disanitasi server (HtmlSanitizer) sebelum disimpan. -->
            <div class="prose-moso" v-html="html" />
        </div>
    </section>
</template>
