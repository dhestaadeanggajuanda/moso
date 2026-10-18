<script setup lang="ts">
/**
 * Merender JSON blok Page Builder (`pages.content_blocks` yang sudah dihidrasi
 * BlockHydrator) memakai komponen dari tema aktif:
 * Themes/{theme}/Components/{block.type}.vue.
 *
 * Komponen tema menerima props blok sebagai props datar, ditambah `data`.
 */
import { computed } from 'vue';
import BlockBoundary from '@/Core/BlockBoundary.vue';
import FallbackBlock from '@/Core/FallbackBlock.vue';
import { themeComponent } from '@/Core/themes';
import type { HydratedBlock } from '@/types/blocks';

const props = defineProps<{
    pageData: HydratedBlock[];
    theme: string;
}>();

const rendered = computed(() =>
    props.pageData.map((block) => ({
        block,
        component: themeComponent(props.theme, 'Components', block.type),
    })),
);
</script>

<template>
    <div class="page-blocks">
        <BlockBoundary
            v-for="{ block, component } in rendered"
            :key="block.id"
            :type="block.type"
        >
            <component
                :is="component"
                v-if="component"
                v-bind="block.props"
                :data="block.data"
            />
            <FallbackBlock v-else :type="block.type" />
        </BlockBoundary>
    </div>
</template>
