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
    <section class="bg-canvas py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- HTML sudah disanitasi server (HtmlSanitizer) sebelum disimpan. -->
            <div class="prose-moso" v-html="html" />
        </div>
    </section>
</template>
