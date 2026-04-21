<script setup>
import { cn } from "@/lib/utils"

const props = defineProps({
  class: {
    type: String,
    default: ""
  },
  placeholder: {
    type: String,
    default: ""
  },
  disabled: {
    type: Boolean,
    default: false
  },
  min: {
    type: Number,
    default: 0
  },
  max: {
    type: Number,
    default: null
  },
  step: {
    type: [Number, String],
    default: "any"
  },
  error: {
    type: [String, Boolean],
    default: false
  },
  unit: {
    type: String,
    default: ""
  }
})

const modelValue = defineModel({ 
  default: 0 
})
</script>

<template>
  <div class="relative w-full">
    <input
      v-model="modelValue"
      type="number"
      :min="min ?? 0"
      :max="max"
      :step="step ?? 'any'"
      :disabled="disabled"
      :placeholder="placeholder"
      :aria-invalid="!!error"
      data-slot="number-input"
      :class="cn(
        'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
        
        '[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none',
        
        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        
        'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
        
        props.class,
      )"
    >
    <div v-if="unit" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
      <span class="text-muted-foreground text-xs font-medium">{{ unit }}</span>
    </div>
  </div>
</template>