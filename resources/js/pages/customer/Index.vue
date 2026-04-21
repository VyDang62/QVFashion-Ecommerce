
<script setup>
import { Head } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import HeroSlider from '@/components/customer/Sections/HeroSlider.vue';
import ProductCard from '@/components/customer/Global/ProductCard.vue';
// Nhận dữ liệu từ Laravel Controller gửi qua Inertia
const props = defineProps({
    popularProducts: Array,
    latestProducts: Array,
    banners:Array,
});
</script>
<template>
  <CustomerLayout>
    <Head title="Trang chủ"/>
    <HeroSlider :slides="banners"/>

    <section id="popular-products" class="py-10">
      <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold mb-8 tracking-wider">Sản phẩm nổi bật</h2>
        <div class="flex flex-wrap -mx-4">
          <ProductCard 
            v-for="product in popularProducts" 
            :key="'popular-' + product.id" 
            :product="product" 
          />
        </div>
      </div>
    </section>

    <section id="latest-products" class="py-10">
      <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold mb-8 tracking-wider">Sản phẩm mới</h2>
        <div class="flex flex-wrap -mx-4">
          <ProductCard 
            v-for="product in latestProducts" 
            :key="'latest-' + product.id" 
            :product="product" 
          />
        </div>
      </div>
    </section>

  </CustomerLayout>
</template>
