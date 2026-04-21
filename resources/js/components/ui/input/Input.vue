<script setup>
import { cn } from "@/lib/utils"

const props = defineProps({
  class: { type: String, default: ""},
  error: { type: [String, Boolean], default: false},
  disabled: { type: Boolean, default: false}
})

const modelValue = defineModel({
  default: "",
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :aria-invalid="!!error"
    :disabled="disabled"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
      'disabled:bg-gray-100/50',
      props.class,
    )"
  >
</template>