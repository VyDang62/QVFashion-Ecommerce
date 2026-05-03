<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import { useFormatter } from '@/composables/useFormatter';
import { usePermission } from '@/composables/usePermission';

const { can } = usePermission();
const props = defineProps({
    flashSale: Object,
    stats: Object
});

const { formatPrice, formatDateTime } = useFormatter();

const statusInfo = computed(() => {
    if (!props.flashSale.is_active) return { label: 'Tạm dừng', color: 'light' };

    const now = new Date();
    const start = new Date(props.flashSale.start_date);
    const end = new Date(props.flashSale.end_date);

    if (now < start) return { label: 'Sắp diễn ra', color: 'warning' };
    if (now >= start && now <= end) return { label: 'Đang diễn ra', color: 'success' };
    return { label: 'Đã kết thúc', color: 'error' };
});

const salesProgress = computed(() => {
    if (props.stats.total_quantity === 0) return 0;
    return Math.round((props.stats.total_sold / props.stats.total_quantity) * 100);
});
</script>

<template>
    <Head :title="'Flash Sale: ' + flashSale.name" />
    <AdminLayout>
        <PageBreadcrumb :pageTitle="'Chi tiết: ' + flashSale.name" parentName="Flash Sale" :parentRoute="route('admin.flashsales.index')" />

        <div class="space-y-6">
            <div class="mb-6 flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-blue-50 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-md font-black text-gray-800 uppercase">{{ flashSale.name }}</h2>
                        <Badge size="sm" :color="statusInfo.color">{{ statusInfo.label }}</Badge>
                    </div>
                </div>
                <Link v-if="can('flashsales.edit')" :href="route('admin.flashsales.edit', flashSale.id)" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                    SỬA FLASH SALE
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Doanh thu ước tính</p>
                    <p class="text-xl font-black text-gray-800">{{ formatPrice(stats.total_revenue) }}</p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase">Sản phẩm đã bán</p>
                    <p class="text-2xl font-black text-blue-600 mt-1">{{ stats.total_sold }} <span class="text-sm text-gray-500">/ {{ stats.total_quantity }}</span></p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tỉ lệ hoàn thành</p>
                    <div class="flex items-end justify-between mb-2">
                        <p class="text-xl font-black text-gray-800">{{ salesProgress }}%</p>
                        <span class="text-md text-gray-500 font-semibold">Còn: {{ stats.total_quantity - stats.total_sold }}</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 transition-all duration-1000" :style="{ width: salesProgress + '%' }"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <ComponentCard title="Thời gian diễn ra">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400 font-bold">Thời điểm bắt đầu</p>
                                    <p class="text-sm font-bold text-gray-700">{{ formatDateTime(flashSale.start_date) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400 font-bold">Thời điểm kết thúc</p>
                                    <p class="text-sm font-bold text-gray-700">{{ formatDateTime(flashSale.end_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </ComponentCard>
                </div>

                <div class="lg:col-span-2">
                    <ComponentCard title="Danh sách sản phẩm tham gia">
                        <div class="overflow-hidden rounded-xl border border-gray-100">
                            <table class="w-full">
                                <thead class="bg-gray-50 text-xs text-gray-400 font-black uppercase">
                                    <tr>
                                        <th class="px-4 py-4 text-left">Sản phẩm</th>
                                        <th class="px-4 py-4 text-left">Giá (Gốc/Sale)</th>
                                        <th class="px-4 py-4 text-left">Chỉ tiêu</th>
                                        <th class="px-4 py-4 text-right">Tiến độ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="item in flashSale.items" :key="item.id" class="group hover:bg-gray-50/80 transition-all cursor-default">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 rounded-lg border border-gray-100 overflow-hidden flex-shrink-0 shadow-sm bg-white">
                                                    <img v-if="item.variant?.primary_thumbnail" :src="item.variant.primary_thumbnail" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-black text-gray-800">{{ item.variant?.sku }}</span>
                                                    <span class="text-[12px] text-gray-500">{{ item.variant?.product?.product_name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-[12px] text-gray-400 line-through">{{ formatPrice(item.variant?.price) }}</span>
                                                <span class="text-sm font-black text-red-500">{{ formatPrice(item.sale_price) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-black text-blue-600">{{ item.sold_quantity }}</span>
                                                <span class="text-[12px] text-gray-500 font-bold uppercase">/ {{ item.sale_quantity }} SP</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col items-end gap-1.5">
                                                <span class="text-[12px] font-black text-gray-800">{{ Math.round((item.sold_quantity / item.sale_quantity) * 100) }}%</span>
                                                <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-orange-400" :style="{ width: (item.sold_quantity / item.sale_quantity) * 100 + '%' }"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!flashSale.items?.length">
                                        <td colspan="4" class="py-20 text-center">
                                            <p class="text-sm text-gray-500 italic">Flash Sale này chưa có sản phẩm nào.</p>
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