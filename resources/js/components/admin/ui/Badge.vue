<template>
  <span :class="[baseStyles, sizeClass, colorStyles]">
    <span v-if="startIcon" class="mr-1">
      <component :is="startIcon" />
    </span>
    <slot></slot>
    <span v-if="endIcon" class="ml-1">
      <component :is="endIcon" />
    </span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type BadgeVariant = 'light' | 'solid'
type BadgeSize = 'sm' | 'md'
type BadgeColor = 'primary' | 'success' | 'error' | 'warning' | 'info' | 'light' | 'dark' | 'teal'

interface BadgeProps {
  variant?: BadgeVariant
  size?: BadgeSize
  color?: BadgeColor
  startIcon?: object
  endIcon?: object
}

const props = withDefaults(defineProps<BadgeProps>(), {
  variant: 'light',
  color: 'primary',
  size: 'md',
})

const baseStyles =
  'inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium capitalize'

const sizeStyles = {
  sm: 'text-theme-xs',
  md: 'text-sm',
}

const variants = {
  light: {
    primary: 'bg-brand-50 text-brand-500',
    success: 'bg-success-50 text-success-600',
    error: 'bg-error-50 text-error-600',
    warning: 'bg-warning-50 text-warning-600',
    info: 'bg-blue-light-50 text-blue-light-500',
    light: 'bg-gray-100 text-gray-700',
    dark: 'bg-gray-500 text-white',
    teal: 'bg-teal-100 text-teal-700',
    secondary: 'bg-purple-100 text-purple-600',
    danger: 'bg-rose-100 text-rose-600',
    pink: 'bg-pink-100 text-pink-600', 
    slate: 'bg-slate-100 text-slate-600', 
  },
  solid: {
    primary: 'bg-brand-500 text-white',
    success: 'bg-success-500 text-white',
    error: 'bg-error-500 text-white',
    warning: 'bg-warning-500 text-white',
    info: 'bg-blue-light-500 text-white',
    light: 'bg-gray-400 text-white',
    dark: 'bg-gray-700 text-white',
    teal: 'bg-teal-500 text-white',
    secondary: 'bg-purple-600 text-purple-100',
    danger: 'bg-rose-500 text-white',
    pink: 'bg-pink-500 text-white',
    slate: 'bg-slate-600 text-white',
  },
}

const sizeClass = computed(() => sizeStyles[props.size])
const colorStyles = computed(() => variants[props.variant][props.color])
</script>
