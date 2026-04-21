<script setup>
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Link } from '@inertiajs/vue3';
defineProps({
    slides: {
        type: Array,
        default: () => []
    }
});

</script>

<style scoped>
:deep(.swiper-button-prev) {
  left: 50px !important;
  bottom: 40px !important;
}

:deep(.swiper-button-next) {
  right: 50px !important;
  bottom: 40px !important;
}

</style>
<template>
  <section id="product-slider" class="relative w-full h-[450px] md:h-[700px] overflow-hidden">
    <swiper
      v-if="slides && slides.length"
      :modules="[Navigation, Pagination, Autoplay]"
      :navigation="true"
      :pagination="{ clickable: true }"
      :autoplay="{ delay: 5000 }"
      class="h-full w-full"
    >
      <swiper-slide v-for="(slide, index) in slides" :key="index" class="relative">
        <img :src="slide.image" class="w-full h-full object-cover">
        <div class="absolute inset-0 flex items-end justify-start p-12 md:p-24 text-left">
          <div class="max-w-2xl">
            <h2 class="text-4xl md:text-7xl font-bold text-white mb-4">{{ slide.title }}</h2>
            <p class="text-white md:text-2xl mb-6">{{ slide.desc }}</p>
            <Link v-if="slide.link" :href="slide.link" class="bg-primary hover:bg-transparent text-white hover:text-white border border-transparent hover:border-white font-semibold px-4 py-2 rounded-full inline-block">
              Shop Now
            </Link>
          </div>
        </div>
      </swiper-slide>
    </swiper>
  </section>
</template>

