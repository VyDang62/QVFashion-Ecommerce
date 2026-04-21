<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import { useFormatter } from '@/composables/useFormatter';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    flashSale: Object,
    stats: Object
});

const { formatPrice, formatDateTime } = useFormatter();

//Xác định trạng thái thời gian
const statusInfo = computed(() => {
    const now = new Date();
    const start = new Date(props.flashSale.start_date);
    const end = new Date(props.flashSale.end_date);

    if (now < start) return { label: 'Sắp diễn ra', color: 'text-blue-600 bg-blue-50' };
    if (now >= start && now <= end) return { label: 'Đang diễn ra', color: 'text-green-600 bg-green-50 border-green-200' };
    return { label: 'Đã kết thúc', color: 'text-red-600 bg-red-50' };
});

//Tính % tiến độ bán hàng tổng thể
const salesProgress = computed(() => {
    if (props.stats.total_quantity === 0) return 0;
    return Math.round((props.stats.total_sold / props.stats.total_quantity) * 100);
});
</script>

<template>
    <Head :title="'Chi tiết: ' + flashSale.name" />
    <AdminLayout>
        <PageBreadcrumb :pageTitle="flashSale.name" parentName="Flash Sale" :parentRoute="route('admin.flashsales.index')" />

        <div class="space-y-6">
            <div class="flex justify-end gap-3">
                <Link v-if="can('flashsales.edit')" :href="route('admin.flashsales.edit', flashSale.id)" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                    CHỈNH SỬA
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Doanh thu ước tính</p>
                    <p class="text-xl font-black text-gray-800">{{ formatPrice(stats.total_revenue) }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tổng sản phẩm đã bán</p>
                    <p class="text-xl font-black text-blue-600">{{ stats.total_sold }} / {{ stats.total_quantity }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tỉ lệ sử dụng</p>
                    <div class="flex items-center gap-2">
                        <p class="text-xl font-black text-gray-800">{{ salesProgress }}%</p>
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" :style="{ width: salesProgress + '%' }"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-center">
                    <span :class="['text-center py-1 rounded-lg text-xs font-bold uppercase border', statusInfo.color]">
                        {{ statusInfo.label }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1">
                    <ComponentCard title="Thông tin Flash Sale">
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Tên:</span>
                                <span class="font-bold text-gray-800">{{ flashSale.name }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Bắt đầu:</span>
                                <span class="font-medium">{{ formatDateTime(flashSale.start_date) }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Kết thúc:</span>
                                <span class="font-medium">{{ formatDateTime(flashSale.end_date) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Hoạt động:</span>
                                <span :class="flashSale.is_active ? 'text-green-600' : 'text-red-600'" class="font-bold">
                                    {{ flashSale.is_active ? 'ĐANG BẬT' : 'ĐANG TẮT' }}
                                </span>
                            </div>
                        </div>
                    </ComponentCard>
                </div>

                <div class="lg:col-span-2">
                    <ComponentCard title="Các sản phẩm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-sm text-gray-400 uppercase border-b">
                                    <tr>
                                        <th class="pb-3">Sản phẩm</th>
                                        <th class="pb-3">Giá (Gốc/Sale)</th>
                                        <th class="pb-3">SL Sale</th>
                                        <th class="pb-3">Đã bán</th>
                                        <th class="pb-3 text-right">Tiến độ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="item in flashSale.items" :key="item.id" class="group">
                                        <td class="py-4">
                                            <div class="flex items-center gap-2">
                                                <span></span>
                                                <div class="w-10 h-10 rounded border overflow-hidden flex-shrink-0">
                                                    <img v-if="item.variant?.primary_thumbnail" :src="item.variant.primary_thumbnail" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-gray-800">{{ item.variant?.sku }}</p>
                                                    <p class="text-sm text-gray-400 truncate w-32">{{ item.variant?.product?.product_name }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4">
                                            <p class="text-sm text-gray-400 line-through">{{ formatPrice(item.variant?.price) }}</p>
                                            <p class="text-sm font-bold text-red-500">{{ formatPrice(item.sale_price) }}</p>
                                        </td>
                                        <td class="py-4 text-sm font-medium">{{ item.sale_quantity }}</td>
                                        <td class="py-4 text-sm font-black text-blue-600">{{ item.sold_quantity }}</td>
                                        <td class="py-4">
                                            <div class="flex flex-col items-end gap-1">
                                                <span class="text-sm font-bold text-gray-400">{{ Math.round((item.sold_quantity / item.sale_quantity) * 100) }}%</span>
                                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-orange-400" :style="{ width: (item.sold_quantity / item.sale_quantity) * 100 + '%' }"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </ComponentCard>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>