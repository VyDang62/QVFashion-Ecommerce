<template>
  <div :class="['rounded-xl border p-4', variantClasses[variant].container]">
    <div class="flex items-start gap-3">
      <div :class="['-mt-0.5', variantClasses[variant].icon]">
        <component :is="icons[variant]" />
      </div>

      <div>
        <h4 class="mb-1 text-sm font-semibold text-gray-800">
          {{ title }}
        </h4>

        <p class="text-sm text-gray-500">{{ message }}</p>

        <router-link
          v-if="showLink"
          :to="linkHref"
          class="inline-block mt-3 text-sm font-medium text-gray-500 underline"
        >
          {{ linkText }}
        </router-link>
      </div>
    </div>
    <button 
      @click="$emit('close')" 
      class="absolute top-3 right-3 p-1 rounded-lg transition-colors hover:bg-black/5"
      :class="variantClasses[variant].icon"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { SuccessIcon, ErrorIcon, WarningIcon, InfoCircleIcon } from '@/icons'
import { computed } from 'vue'

interface AlertProps {
  variant: 'success' | 'error' | 'warning' | 'info'
  title: string
  message: string
  showLink?: boolean
  linkHref?: string
  linkText?: string
}

defineEmits(['close']);

const props = withDefaults(defineProps<AlertProps>(), {
  showLink: false,
  linkHref: '#',
  linkText: 'Learn more',
})

const variantClasses = {
  success: {
    container: 'border-success-500 bg-success-50',
    icon: 'text-success-500',
  },
  error: {
    container: 'border-error-500 bg-error-50',
    icon: 'text-error-500',
  },
  warning: {
    container: 'border-warning-500 bg-warning-50',
    icon: 'text-warning-500',
  },
  info: {
    container:
      'border-blue-light-500 bg-blue-light-50',
    icon: 'text-blue-light-500',
  },
}

const icons = {
  success: SuccessIcon,
  error: ErrorIcon,
  warning: WarningIcon,
  info: InfoCircleIcon,
}
</script>
