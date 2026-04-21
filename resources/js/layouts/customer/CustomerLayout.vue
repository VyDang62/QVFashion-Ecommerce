<script setup>
import Header from '@/components/customer/Global/Header.vue';
import Footer from '@/components/customer/Global/Footer.vue';
import CustomerFlashMessage from '@/components/common/CustomerFlashMessage.vue';
</script>
<template>
  <div class="min-h-screen flex flex-col font-manrope text-gray-dark">
    <Header />
    <CustomerFlashMessage/>
    <main class="flex-grow w-full">
      <slot /> </main>
    <!-- <Newsletter /> -->
    <Footer />
  </div>
</template>

