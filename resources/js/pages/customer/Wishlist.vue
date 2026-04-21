<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import UserCenterSidebar from '@/layouts/customer/UserCenterSidebar.vue';
import { useFormatter } from '@/composables/useFormatter';
import { route } from 'ziggy-js';

const { formatPrice } = useFormatter();

const props = defineProps({
    wishlistItems: Array,
});

const isProcessing = ref(false);

const removeWishlist = (productId) => {
    router.post(route('wishlist.store'), {
        product_id: productId
    }, {
        preserveScroll: true,
        onStart: () => isProcessing.value = true,
        onFinish: () => isProcessing.value = false,
    });
};
</script>

<template>
    <Head title="Sản phẩm yêu thích" />

    <CustomerLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row gap-6">
                    
                    <aside class="w-full md:w-1/4">
                        <UserCenterSidebar active="wishlist" />
                    </aside>

                    <main class="w-full md:w-3/4">
                        <div class="bg-white p-6 shadow-sm border border-gray-100 rounded-2xl sm:p-8">
                            <header class="mb-8 flex justify-between items-center border-b pb-5">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">Wishlist</h2>
                                    <p class="mt-1 text-sm text-gray-600">Bạn có {{ wishlistItems.length }} sản phẩm đã lưu</p>
                                </div>
                            </header>

                            <div v-if="wishlistItems.length === 0" class="py-20 text-center">
                                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="far fa-heart text-gray-200 text-5xl"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">Chưa có sản phẩm yêu thích</h3>
                                <Link :href="route('shop.index')" class="mt-4 bg-primary text-white px-10 py-3 rounded-full font-bold hover:shadow-lg transition-all text-xs uppercase inline-block">
                                    Mua sắm ngay
                                </Link>
                            </div>

                            <div v-else class="space-y-6">
                                <div v-for="item in wishlistItems" :key="item.id" 
                                    class="group flex flex-col sm:flex-row items-center gap-6 p-4 rounded-2xl border border-transparent hover:border-gray-100 hover:bg-gray-50/50 transition-all duration-300"
                                >
                                    <Link :href="route('product.show', item.slug)" 
                                        class="relative w-1/3 h-full sm:w-32 h-40 flex-shrink-0 overflow-hidden rounded-xl bg-gray-50 border border-gray-100 block"
                                    >
                                        <img 
                                            :src="item.image" 
                                            class="w-full h-full object-cover origin-top transition-transform duration-500 ease-in-out scale-[1.5] group-hover:scale-100" 
                                            alt="product image"
                                        >
                                        
                                        <div v-if="item.active_flash_sales.length > 0" class="absolute top-2 left-2 bg-red-600 text-white text-[9px] font-black px-2 py-1 rounded shadow-lg z-10 uppercase">
                                            -{{ item.active_flash_sales[0].discount_percent }}%
                                        </div>
                                    </Link>

                                    <div class="flex-grow flex flex-col h-40 justify-between py-1 text-center sm:text-left">
                                        <div>
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                                                <Link :href="route('product.show', item.slug)" class="text-lg font-bold text-gray-900 uppercase hover:text-primary transition-colors line-clamp-1">
                                                    {{ item.name }}
                                                </Link>
                                                <button @click="removeWishlist(item.id)" :disabled="isProcessing" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">{{ item.category }}</p>
                                        </div>

                                        <div class="mt-auto flex flex-col sm:flex-row justify-between items-center gap-4">
                                            <div class="flex flex-col items-center sm:items-start">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xl font-black text-primary">{{ formatPrice(item.price) }}</span>
                                                    <span v-if="item.old_price" class="text-sm line-through text-gray-400">
                                                        {{ formatPrice(item.old_price) }}
                                                    </span>
                                                </div>
                                                <p v-if="item.active_flash_sales.length > 0" class="text-[10px] text-orange-500 font-bold uppercase mt-1 flex items-center gap-1">
                                                    <i class="fas fa-bolt"></i> Đang Flash Sale
                                                </p>
                                            </div>

                                            <Link :href="route('product.show', item.slug)" 
                                                class="px-8 py-2.5 bg-gray-900 text-white text-[11px] font-black rounded-full hover:bg-primary transition-all uppercase tracking-tighter shadow-md active:scale-95"
                                            >
                                                Xem sản phẩm
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>