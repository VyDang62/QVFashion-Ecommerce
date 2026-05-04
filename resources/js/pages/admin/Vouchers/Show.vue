<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import { useFormatter } from '@/composables/useFormatter';
import { usePermission } from '@/composables/usePermission';
import Badge from '@/components/admin/ui/Badge.vue';

const { can } = usePermission();
const props = defineProps({
    voucher: Object,
    stats: Object,
});

const { formatPrice, formatDateTime } = useFormatter();

const statusInfo = computed(() => {
    if (!props.voucher.is_active) return { label: 'Tạm dừng', color: 'light' };
    
    const now = new Date();
    const start = new Date(props.voucher.start_date);
    const end = props.voucher.end_date ? new Date(props.voucher.end_date) : null;

    if (now < start) return { label: 'Chờ kích hoạt', color: 'warning' };
    if (end && now > end) return { label: 'Đã hết hạn', color: 'error' };
    return { label: 'Đang chạy', color: 'success' };
});

const usageProgress = computed(() => {
    if (!props.voucher.usage_limit) return 0;
    return Math.min(Math.round((props.voucher.used_count / props.voucher.usage_limit) * 100), 100);
});

const displayDiscountValue = computed(() => {
    if (props.voucher.voucher_type === 'percentage') {
        return `${props.voucher.discount_value}%`;
    }
    return formatPrice(props.voucher.discount_value);
});
</script>

<template>
    <Head :title="'Voucher: ' + voucher.code" />
    <AdminLayout>
        <PageBreadcrumb :pageTitle="'Chi tiết mã ' + voucher.code" parentName="Voucher" :parentRoute="route('admin.vouchers.index')" />

        <div class="space-y-6">
            <div class="mb-6 flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-blue-50 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-md font-black text-gray-800">{{ voucher.code }}</h2>
                        <Badge size="sm" :color="statusInfo.color">{{ statusInfo.label }}</Badge>
                    </div>
                </div>
                <Link v-if="can('vouchers.edit')" :href="route('admin.vouchers.edit', voucher.id)" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                    SỬA VOUCHER
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tổng tiền đã giảm</p>
                    <p class="text-xl font-black text-gray-800">{{ formatPrice(stats.total_discount_amount || 0) }}</p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase">Lượt đã dùng</p>
                    <p class="text-2xl font-black text-blue-600 mt-1">{{ voucher.used_count }} <span class="text-sm text-gray-500">/ {{ voucher.usage_limit || '∞' }}</span></p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tiến độ sử dụng</p>
                    <template v-if="voucher.usage_limit > 0">
                        <div class="flex items-end justify-between mb-2">
                            <p class="text-xl font-black text-gray-800">{{ usageProgress }}%</p>
                            <span class="text-md text-gray-500 font-semibold">
                                Còn: {{ voucher.usage_limit - voucher.used_count }}
                            </span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-blue-600 transition-all duration-1000" 
                                :style="{ width: usageProgress + '%' }"
                            ></div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="flex flex-col justify-center pt-2">
                            <div class="flex items-center gap-2">
                                <p class="text-md font-black text-blue-600 uppercase">Không giới hạn</p>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <p class="text-xs text-gray-400 font-bold uppercase">Giảm tối đa</p>
                    <p class="text-xl font-black text-red-500 mt-1">{{formatPrice(voucher?.max_discount_amount)}}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <ComponentCard title="Cấu hình Voucher">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between group">
                                <span class="text-gray-400 text-sm font-bold">Mức giảm giá</span>
                                <span class="text-sm font-black text-gray-800">{{ displayDiscountValue }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 text-sm font-bold">Đơn tối thiểu</span>
                                <span class="text-sm font-bold text-gray-700">{{ formatPrice(voucher.min_order_value) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 text-sm font-bold">Giới hạn/User</span>
                                <span class="px-2 py-1 bg-gray-100 rounded-lg font-black text-gray-700 text-xs">{{ voucher.per_user_limit }} LẦN</span>
                            </div>
                            <div class="h-px bg-gray-50"></div>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-400 font-bold">Ngày bắt đầu</p>
                                        <p class="text-sm font-bold text-gray-700">{{ formatDateTime(voucher.start_date) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-400 font-bold">Ngày kết thúc</p>
                                        <p class="text-sm font-bold text-gray-700">{{ formatDateTime(voucher.end_date) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Điều kiện áp dụng">
                        <div v-if="voucher.restrictions?.length > 0" class="flex flex-wrap gap-2">
                            <div v-for="res in voucher.restrictions" :key="res.id" class="flex items-center gap-2 p-2 bg-blue-50/50 rounded-xl border border-blue-100">
                                <span class="w-2 h-2 rounded-full" 
                                    :class="{
                                        'bg-orange-500': res.restrict_type === 'product',
                                        'bg-green-500': res.restrict_type === 'category',
                                        'bg-blue-500': res.restrict_type === 'brand'
                                    }">
                                </span>

                                <span class="text-[9px] font-black text-blue-800 uppercase px-1.5 py-0.5 bg-blue-100 rounded-md">
                                    {{ res.restrict_type }}
                                </span>
                                <span class="text-xs font-bold text-gray-700">
                                    {{ 
                                        res.restricted_item?.product_name || 
                                        res.restricted_item?.category_name || 
                                        res.restricted_item?.name || 
                                        'N/A' 
                                    }}
                                </span>
                                <span class="text-[9px] font-medium text-gray-400">#{{ res.restrict_id }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-xs text-gray-400 font-medium">Áp dụng cho toàn bộ cửa hàng</p>
                        </div>
                    </ComponentCard>
                </div>

                <div class="lg:col-span-2">
                    <ComponentCard title="Nhật ký sử dụng">
                        <div class="overflow-hidden rounded-xl border border-gray-100">
                            <table class="w-full">
                                <thead class="bg-gray-50 text-xs text-gray-400 font-black uppercase">
                                    <tr>
                                        <th class="px-4 py-4 text-left">Khách hàng</th>
                                        <th class="px-4 py-4 text-left">Đơn hàng</th>
                                        <th class="px-4 py-4 text-left">Giảm giá</th>
                                        <th class="px-4 py-4 text-right">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="usage in voucher.usages" :key="usage.id" class="group hover:bg-gray-50/80 transition-all cursor-default">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 font-bold text-xs uppercase">
                                                    {{ usage.user?.full_name?.charAt(0) || 'A' }}
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-black text-gray-800">{{ usage.user?.full_name}}</span>
                                                    <span class="text-[12px] text-gray-500">{{ usage.user?.email }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm">
                                            <Link :href="route('admin.orders.show', usage.order_id)" class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg font-bold hover:bg-blue-600 hover:text-white transition-colors">
                                                #{{ usage.order?.order_code?.split('-')[0] || usage.order_id }}
                                            </Link>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="text-sm font-black text-red-500">-{{ formatPrice(usage.discount_amount) }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <span class="text-[12px] text-gray-500 font-bold uppercase">{{ formatDateTime(usage.created_at) }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!voucher.usages?.length">
                                        <td colspan="4" class="py-20 text-center">
                                            <p class="text-sm text-gray-500 italic">Mã này chưa được khách hàng nào sử dụng.</p>
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