<script setup>
import ThemeProvider from '@/layouts/admin/ThemeProvider.vue';
import SidebarProvider from '@/layouts/admin/SidebarProvider.vue';
import AdminLayoutContent from './AdminLayoutContent.vue'; 
import FlashMessages from '@/components/common/FlashMessages.vue';
</script>

<template>
  
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayoutContent>
        <FlashMessages />
        <slot  :key="$page.url"></slot>
      </AdminLayoutContent>
    </SidebarProvider>
  </ThemeProvider>
</template>