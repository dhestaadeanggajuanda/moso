<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

withDefaults(
    defineProps<{
        id: string;
        name: string;
        label: string;
        accept: string;
        help: string;
        error?: string;
        required?: boolean;
        progress?: { percentage?: number } | null;
    }>(),
    { required: false, progress: null, error: undefined },
);
</script>

<template>
    <div class="grid gap-1.5">
        <Label :for="id">{{ label }}</Label>
        <slot name="current" />
        <input
            :id="id"
            :name="name"
            type="file"
            :accept="accept"
            :required="required"
            :aria-describedby="`${id}-help`"
            :aria-invalid="!!error"
            class="text-sm file:mr-3 file:min-h-9 file:rounded-md file:border file:bg-transparent file:px-3 file:py-1.5 file:text-sm"
        />
        <p :id="`${id}-help`" class="text-xs text-muted-foreground">
            {{ help }}
        </p>
        <progress
            v-if="progress?.percentage"
            :value="progress.percentage"
            max="100"
            class="w-full"
        >
            {{ progress.percentage }}%
        </progress>
        <InputError :message="error" />
    </div>
</template>
