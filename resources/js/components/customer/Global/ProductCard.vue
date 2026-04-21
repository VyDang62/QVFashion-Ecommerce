<template>
  <div class="w-full h-full sm:w-1/2 lg:w-1/4 px-4 mb-8">
    <div class="bg-white p-4 rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 group border border-gray-100 flex flex-col h-full relative max-h-[700px]">
      <button 
        @click="toggleWishlist"
        :disabled="isProcessing"
        :class="[
          'absolute top-6 right-6 z-20 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300 border shadow-sm active:scale-90',
          isInWishlist 
            ? 'bg-white border-red-100 text-red-500' 
            : 'bg-white border-gray-200 text-gray-400 hover:text-black hover:border-black'
        ]"
      >
        <i :class="[isInWishlist ? 'fas fa-heart' : 'far fa-heart', 'text-sm']"></i>
      </button>

      <Link :href="route('product.show', product.slug)" class="block max-h-[450px] relative overflow-hidden rounded-lg mb-4 h-118 bg-gray-50">
        <img 
          :src="product.image" 
          class="w-full h-full object-cover origin-top transition-transform duration-500 ease-in-out scale-[1.5] group-hover:scale-100"
          alt="product image"
        >
        
        <div v-if="hasFlashSale" class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-black px-2 py-1 rounded shadow-lg z-10 flex items-center gap-1 uppercase">
          <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
          Flash Sale -{{ flashSaleData.discount_percent }}%
        </div>
      </Link>

      <div class="flex-grow min-h-[160px]">
        <Link :href="route('product.show', product.slug)" class="text-lg font-semibold mb-2 block hover:text-primary transition-colors line-clamp-2 uppercase">
          {{ product.name }}
        </Link>
        <p class="my-2 text-gray-400 text-sm italic">{{ product.category }}</p>
        
        <div class="flex flex-col">
          <div class="flex items-center h-7"> <span class="text-lg font-bold text-primary">{{ formatPrice(product.price) }}</span>
            <span v-if="product.old_price" class="text-sm line-through ml-2 text-gray-400">
              {{ formatPrice(product.old_price) }}
            </span>
          </div>

          <div class="h-10 flex items-center"> <div v-if="hasFlashSale" class="w-full">
              <div class="relative w-full h-5 bg-orange-100 rounded-full overflow-hidden border border-orange-200">
                <div 
                  class="h-full bg-gradient-to-r from-orange-500 to-red-600 transition-all duration-700"
                  :style="{ width: progressWidth + '%' }"
                ></div>
                <span class="absolute inset-0 flex items-center justify-center text-[12px] font-bold text-black uppercase tracking-tighter shadow-sm">
                  {{ saleStatusText }}
                </span>
              </div>
            </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useFormatter } from '@/composables/useFormatter';

const props = defineProps({ product: Object });
const { formatPrice } = useFormatter();

const flashSaleData = computed(() => {
  return props.product.active_flash_sales && props.product.active_flash_sales.length > 0 
    ? props.product.active_flash_sales[0] 
    : null;
});

const hasFlashSale = computed(() => !!flashSaleData.value);
//Lấy thanh trạng thái
const progressWidth = computed(() => {
  if (!hasFlashSale.value) return 0;
  const p = (flashSaleData.value.sold_quantity / flashSaleData.value.sale_quantity) * 100;
  return Math.max(p, 15);
});
//Lấy trạng thái sale
const saleStatusText = computed(() => {
  if (!hasFlashSale.value) return '';
  if (progressWidth.value >= 90) return 'Sắp hết hàng!';
  return `Đã bán ${flashSaleData.value.sold_quantity}`;
});
//Wishlist
const isProcessingWishlist = ref(false);

const isInWishlist = computed(() => props.product.is_favorited);

const toggleWishlist = (e) => {
    e.preventDefault();
    e.stopPropagation();

    router.post(route('wishlist.store'), {
        product_id: props.product.id
    }, {
        preserveScroll: true,
        onStart: () => isProcessingWishlist.value = true,
        onFinish: () => isProcessingWishlist.value = false,
    });
};
</script>