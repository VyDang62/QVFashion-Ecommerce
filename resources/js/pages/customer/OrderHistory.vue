<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import UserCenterSidebar from '@/layouts/customer/UserCenterSidebar.vue';
import { useFormatter } from '@/composables/useFormatter';
const {formatPrice, formatDateTime} = useFormatter();

const props = defineProps({
    orders: Array,
});
const isProcessing = ref(false);
const handleUpdateStatus = (orderCode, newStatus) => {
    router.patch(route('orderhistory.update', orderCode), {
        order_status: newStatus
    }, {
        preserveScroll: true,
        onStart: () => isProcessing.value = true,
        onFinish: () => isProcessing.value = false,
    });
};
</script>
<template>
    <Head title="Lịch sử đơn hàng" />

    <CustomerLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row gap-6">
                    
                    <aside class="w-full md:w-1/4">
                        <UserCenterSidebar active="orders" />
                    </aside>

                    <main class="w-full md:w-3/4 space-y-6">
                        <div class="bg-white p-4 shadow-sm border border-gray-100 sm:rounded-lg sm:p-8">
                            <section>
                                <header>
                                    <h2 class="text-xl font-semibold text-gray-900">Lịch sử đặt hàng</h2>
                                </header>
                                <div v-if="orders.length === 0" class="py-12 text-center">
                                    <div class="mb-6">
                                        <i class="fas fa-file-invoice text-gray-200 text-8xl"></i>
                                    </div>
                                    <h2 class="text-lg font-bold text-gray-800 mb-2">Bạn chưa có đơn hàng nào</h2>
                                    <Link :href="route('shop.index')" class="bg-primary text-white px-8 py-3 rounded-full font-bold hover:shadow-lg transition-all inline-block text-sm">
                                        Mua sắm ngay
                                    </Link>
                                </div>

                                <div v-else class="space-y-4 mt-6">
                                    <div v-for="order in orders" :key="order.id" 
                                        class="border border-gray-100 rounded-xl overflow-hidden hover:border-primary transition-colors group"
                                    >
                                        <div class="p-4 sm:p-6">
                                            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-3 bg-gray-50 rounded-lg group-hover:bg-primary/5 transition-colors">
                                                        <i class="fas fa-box text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-gray-900 text-sm uppercase">Mã đơn: {{ order.order_code }}</h3>
                                                        <p class="text-sm text-gray-800">{{ formatDateTime(order.created_at) }}</p>
                                                    </div>
                                                </div>
                                                <span :class="['px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider', order.status_info.bg, order.status_info.class]">
                                                    {{ order.status_info.label }}
                                                </span>
                                            </div>

                                            <div class="divide-y divide-gray-50 border-t border-b border-gray-50 my-4">
                                                <div v-for="detail in order.details.slice(0, 2)" :key="detail.id" class="py-3 flex items-center gap-4">
                                                    <img :src="detail.variant.primary_thumbnail"
                                                        class="w-20 h-20 object-cover rounded-lg border bg-gray-50" 
                                                        alt="product image">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-bold text-gray-800 truncate uppercase">{{ detail.product_name }}</p>
                                                        <p class="text-sm font-semibold text-gray-700 truncate uppercase">{{ detail.variant_info }}</p>
                                                        <p class="text-xs text-gray-600">Số lượng: {{ detail.quantity }}</p>
                                                    </div>
                                                    <p class="text-md font-bold text-gray-900">{{ formatPrice(detail.sub_total) }}</p>
                                                </div>
                                                <p v-if="order.details.length > 2" class="py-2 text-xs text-center text-gray-700">
                                                    và {{ order.details.length - 2 }} sản phẩm khác...
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap justify-between items-center gap-4 pt-2">
                                                <div>
                                                    <p class="text-sm font-bold uppercase">Tổng thanh toán:</p>
                                                    <p class="text-lg font-black text-primary">{{ formatPrice(order.final_amount) }}</p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <Link :href="route('orderhistory.show', order.order_code)" 
                                                        class="px-6 py-2 border-2 border-gray-900 text-gray-900 rounded-full font-bold text-xs hover:bg-gray-900 hover:text-white transition-all uppercase"
                                                    >
                                                        Chi tiết
                                                    </Link>
                                                    <button 
                                                        v-if="[1].includes(order.order_status)"
                                                        @click="handleUpdateStatus(order.order_code, 0)"
                                                        :disabled="isProcessing"
                                                        class="px-6 py-2 border border-red-500 text-red-500 rounded-full font-bold text-xs hover:bg-red-50 transition-all uppercase disabled:opacity-50"
                                                    >
                                                        Hủy đơn
                                                    </button>
                                                    
                                                    <button 
                                                        v-if="order.order_status === 5"
                                                        @click="handleUpdateStatus(order.order_code, 7)"
                                                        :disabled="isProcessing"
                                                        class="px-6 py-2 border border-rose-500 text-rose-600 rounded-full font-bold text-xs hover:bg-rose-50 transition-all uppercase disabled:opacity-50"
                                                    >
                                                        Yêu cầu trả hàng
                                                    </button>
                                                    <button 
                                                        v-if="order.order_status === 5"
                                                        @click="handleUpdateStatus(order.order_code, 6)"
                                                        :disabled="isProcessing"
                                                        class="px-6 py-2 bg-green-600 text-white rounded-full font-bold text-xs hover:bg-green-700 shadow-md transition-all uppercase disabled:opacity-50"
                                                    >
                                                        Hoàn thành
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>