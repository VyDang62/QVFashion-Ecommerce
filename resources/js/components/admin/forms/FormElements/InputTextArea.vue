<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  rows?: number | string
  error?: string | boolean
}>()

const modelValue = defineModel<string | number>({
  default: "",
})
</script>

<template>
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    :rows="rows || 4"
    :aria-invalid="!!error"
    :class="cn(
      'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
      props.class,
    )"
  ></textarea>
</template>