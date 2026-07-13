<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import UserCenterSidebar from '@/layouts/customer/UserCenterSidebar.vue';
import { useFormatter } from '@/composables/useFormatter';
const { formatPrice, formatDateTime } = useFormatter();
const props = defineProps({
    order: Object,
});
</script>

<template>
    <Head :title="'Chi tiết đơn hàng #' + order.id" />

    <CustomerLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row gap-6">
                    
                    <aside class="w-full md:w-1/4">
                        <UserCenterSidebar active="orders" />
                    </aside>

                    <main class="w-full md:w-3/4 space-y-6">
                        <div class="bg-white shadow-sm border border-gray-100 sm:rounded-2xl overflow-hidden">
                            <div class="p-6 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4">
                                <div class="flex items-center gap-4">
                                    <Link :href="route('orderhistory')" class="text-gray-400 hover:text-primary transition-colors">
                                        <i class="fas fa-arrow-left text-xl"></i>
                                    </Link>
                                    <div>
                                        <h1 class="text-md font-black text-gray-900 uppercase">Đơn hàng #{{ order.id }}</h1>
                                        <p class="text-sm text-gray-900 font-medium mt-1 uppercase">Mã đơn: {{ order.order_code }}</p>
                                        <p class="text-sm text-gray-900 font-medium mt-1">Ngày đặt: {{ formatDateTime(order.created_at) }}</p>
                                    </div>
                                </div>
                                <span :class="['px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest shadow-sm', order.status_info.class, order.status_info.bg]">
                                    {{ order.status_info.label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100 bg-gray-50/30">
                                <div class="p-6">
                                    <h3 class="text-xs font-black uppercase text-gray-900 mb-4 tracking-widest"><i class="fas fa-map-marker-alt mr-2 text-primary"></i>Địa chỉ nhận hàng</h3>
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-900">Họ tên người nhận: {{ order.shipping_recipient_name }}</p>
                                        <p class="text-sm font-medium text-gray-900">Số điện thoại: {{ order.shipping_phone_number }}</p>
                                        <p class="text-sm font-medium text-gray-900">
                                            Địa chỉ:
                                            {{ order.shipping_address_detail }}, {{ order.shipping_ward }}, {{ order.shipping_district }}, {{ order.shipping_province }}
                                        </p>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <h3 class="text-xs font-black uppercase text-gray-400900 mb-4 tracking-widest"><i class="fas fa-credit-card mr-2 text-primary"></i>Hình thức thanh toán</h3>
                                    <div class="space-y-2">
                                        <p class="text-sm font-bold text-gray-800">{{ order.payment_method_label }}</p>
                                        <div v-if="order.order_note" class="mt-4 p-3 bg-white rounded-lg border border-dashed border-gray-200">
                                            <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Ghi chú từ bạn:</p>
                                            <p class="text-xs text-gray-600 italic">"{{ order.order_note }}"</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6">
                                <h3 class="text-sm font-black uppercase mb-4 tracking-widest">Sản phẩm trong đơn hàng</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="text-xs font-black text-gray-800 uppercase border-b border-gray-50">
                                                <th class="pb-4">Sản phẩm</th>
                                                <th class="pb-4 text-center">Đơn giá</th>
                                                <th class="pb-4 text-center">Số lượng</th>
                                                <th class="pb-4 text-right">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <tr v-for="detail in order.details" :key="detail.id" class="group">
                                                <td class="py-5">
                                                    <div class="flex items-center gap-4">
                                                        <img :src="detail.variant.primary_thumbnail" class="h-16 w-16 rounded-xl object-cover border border-gray-100" />
                                                        <div>
                                                            <p class="font-bold text-gray-900 text-sm uppercase group-hover:text-primary transition-colors">{{ detail.product_name }}</p>
                                                            <p class="text-xs text-gray-600 font-semibold uppercase tracking-tighter">{{ detail.variant_info }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-5 text-center text-sm font-bold text-gray-900">
                                                    {{ formatPrice(detail.unit_price) }}
                                                </td>
                                                <td class="py-5 text-center text-sm font-bold text-gray-900">
                                                    {{ detail.quantity }}
                                                </td>
                                                <td class="py-5 text-right text-sm font-black text-gray-900">
                                                    {{ formatPrice(detail.sub_total) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="bg-gray-900 p-8 text-white">
                                <div class="max-w-xs ml-auto space-y-3">
                                    <div class="flex justify-between text-sm font-bold text-white">
                                        <span>Tạm tính:</span>
                                        <span>{{ formatPrice(order.total_cost) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm font-bold text-white">
                                        <span>Phí vận chuyển:</span>
                                        <span>+{{ formatPrice(order.shipping_fee || 0) }}</span>
                                    </div>
                                    <div v-if="order.discount_amount > 0" class="flex justify-between text-sm text-red-400 font-bold">
                                        <span>Giảm giá Voucher:</span>
                                        <span>-{{ formatPrice(order.discount_amount) }}</span>
                                    </div>
                                    <div class="pt-4 border-t border-gray-700 flex justify-between items-center">
                                        <span class="text-md font-black">Tổng thanh toán</span>
                                        <span class="text-md font-black text-primary">{{ formatPrice(order.final_amount) }}</span>
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