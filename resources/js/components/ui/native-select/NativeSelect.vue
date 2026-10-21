<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ChevronDown } from "@lucide/vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

// Elemen <select> asli: aksesibel, ramah ponsel, dan ikut terkirim di <Form> Inertia.
// Atribut (name, id, required, aria-*) harus jatuh ke <select>, bukan ke <div> pembungkus:
// tanpa name di <select>, nilainya tidak ikut terkirim.
defineOptions({ inheritAttrs: false })

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number | null
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <div class="relative w-full">
    <select
      v-bind="$attrs"
      v-model="modelValue"
      data-slot="native-select"
      :class="cn(
        'dark:bg-input/30 border-input h-9 w-full appearance-none rounded-md border bg-transparent py-1 pr-9 pl-3 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
        props.class,
      )"
    >
      <slot />
    </select>
    <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 opacity-50" aria-hidden="true" />
  </div>
</template>
