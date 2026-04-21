<script setup>
import { reactiveOmit } from "@vueuse/core"
import { Label } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps({
  class: {
    type: String,
    default: ""
  },
  for: {
    type: String,
    default: null
  }
})

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <Label
    data-slot="label"
    v-bind="delegatedProps"
    :class="
      cn(
        'mb-1.5 block text-sm font-medium text-gray-700',
        props.class,
      )
    "
  >
    <slot />
  </Label>
</template>
