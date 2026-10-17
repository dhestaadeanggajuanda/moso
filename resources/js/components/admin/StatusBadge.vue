<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { StatusValue } from '@/types/admin';

const props = defineProps<{
    status: StatusValue;
}>();

const variant = computed(() => {
    switch (props.status.value) {
        case 'published':
            return 'default';
        case 'revision_required':
            return 'destructive';
        case 'draft':
        case 'archived':
            return 'outline';
        default:
            return 'secondary';
    }
});
</script>

<template>
    <Badge :variant="variant">{{ status.label }}</Badge>
</template>
