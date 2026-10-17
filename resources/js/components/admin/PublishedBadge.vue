<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    published: boolean;
}>();
</script>

<template>
    <Badge :variant="published ? 'default' : 'outline'">
        {{ published ? 'Terbit' : 'Draf' }}
    </Badge>
</template>
