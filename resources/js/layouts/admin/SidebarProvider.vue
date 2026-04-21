<template>
  <slot></slot>
</template>

<script setup lang="ts">
import { useSidebarProvider } from '@/composables/admin/useSidebar'

useSidebarProvider()
</script>
