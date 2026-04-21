<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import { useFormatter } from '@/composables/useFormatter';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    voucher: Object,
    stats: Object, // Bao gồm: total_discount_amount, usage_history
});

const { formatPrice, formatDateTime } = useFormatter();

const statusInfo = computed(() => {
    if (!props.voucher.is_active) return { label: 'Đang tắt', color: 'text-gray-600 bg-gray-50 border-gray-200' };
    
    const now = new Date();
    const start = new Date(props.voucher.start_date);
    const end = props.voucher.end_date ? new Date(props.voucher.end_date) : null;

    if (now < start) return { label: 'Sắp diễn ra', color: 'text-blue-600 bg-blue-50 border-blue-100' };
    if (end && now > end) return { label: 'Hết hạn', color: 'text-red-600 bg-red-50 border-red-100' };
    return { label: 'Đang hoạt động', color: 'text-green-600 bg-green-50 border-green-200' };
});

const usageProgress = computed(() => {
    if (!props.voucher.usage_limit) return 0;
    return Math.round((props.voucher.used_count / props.voucher.usage_limit) * 100);
});

const displayDiscountValue = computed(() => {
    if (props.voucher.voucher_type === 'percentage') {
        return `${props.voucher.discount_value}%`;
    }
    return formatPrice(props.voucher.discount_value);
});
</script>

<template>
    <Head :title="'Chi tiết Voucher: ' + voucher.code" />
    <AdminLayout>
        <PageBreadcrumb :pageTitle="'Voucher: ' + voucher.code" parentName="Quản lý Voucher" :parentRoute="route('admin.vouchers.index')" />

        <div class="space-y-6">
            <div class="flex justify-end gap-3">
                <Link v-if="can('vouchers.edit')" :href="route('admin.vouchers.edit', voucher.id)" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                    CHỈNH SỬA
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tổng tiền đã giảm</p>
                    <p class="text-xl font-black text-gray-800">{{ formatPrice(stats.total_discount_amount || 0) }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Số lượt sử dụng</p>
                    <p class="text-xl font-black text-blue-600">
                        {{ voucher.used_count }} / {{ voucher.usage_limit || '∞' }}
                    </p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tỉ lệ sử dụng</p>
                    <div class="flex items-center gap-2">
                        <p class="text-xl font-black text-gray-800">{{ voucher.usage_limit ? usageProgress : 100 }}%</p>
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" :style="{ width: (voucher.usage_limit ? usageProgress : 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-center">
                    <span :class="['text-center py-1.5 rounded-lg text-xs font-bold uppercase border', statusInfo.color]">
                        {{ statusInfo.label }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <ComponentCard title="Thông tin Voucher">
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Mã Voucher:</span>
                                <span class="font-black text-blue-700 bg-blue-50 px-2 rounded">{{ voucher.code }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Loại giảm giá:</span>
                                <span class="font-bold uppercase text-gray-800">
                                    {{ voucher.voucher_type === 'percentage' ? 'Phần trăm (%)' : 'Số tiền cố định' }}
                                </span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Giá trị giảm:</span>
                                <span class="font-black text-red-600 text-lg">{{ displayDiscountValue }}</span>
                            </div>
                            <div v-if="voucher.max_discount_amount" class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Giảm tối đa:</span>
                                <span class="font-medium">{{ formatPrice(voucher.max_discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Đơn tối thiểu:</span>
                                <span class="font-medium">{{ formatPrice(voucher.min_order_value) }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Giới hạn/User:</span>
                                <span class="font-bold">{{ voucher.per_user_limit }} lần</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Bắt đầu:</span>
                                <span class="font-medium">{{ formatDateTime(voucher.start_date) }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-400">Kết thúc:</span>
                                <span class="font-medium">{{ voucher.end_date ? formatDateTime(voucher.end_date) : 'Không thời hạn' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Hoạt động:</span>
                                <span :class="voucher.is_active ? 'text-green-600' : 'text-red-600'" class="font-bold">
                                    {{ voucher.is_active ? 'ĐANG BẬT' : 'ĐANG TẮT' }}
                                </span>
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Điều kiện áp dụng">
                        <div v-if="voucher.restrictions?.length > 0" class="space-y-3">
                            <div v-for="res in voucher.restrictions" :key="res.id" class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-100">
                                <Badge color="teal" size="sm">{{ res.restrict_type }}</Badge>
                                <span class="text-xs font-bold text-gray-700">ID Đối tượng: {{ res.restrict_id }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-400 text-sm italic">
                            Áp dụng cho toàn bộ sản phẩm
                        </div>
                    </ComponentCard>
                </div>

                <div class="lg:col-span-2">
                    <ComponentCard title="Lịch sử sử dụng gần đây">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-sm text-gray-400 uppercase border-b">
                                    <tr>
                                        <th class="pb-3">Khách hàng</th>
                                        <th class="pb-3">Mã Đơn hàng</th>
                                        <th class="pb-3">Số tiền giảm</th>
                                        <th class="pb-3 text-right">Ngày dùng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="usage in voucher.usages" :key="usage.id" class="group hover:bg-gray-50/50 transition-colors">
                                        <td class="py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-800">{{ usage.user?.full_name || 'Ẩn danh' }}</span>
                                                <span class="text-xs text-gray-400">{{ usage.user?.email }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 text-sm font-medium">
                                            <Link :href="route('admin.orders.show', usage.order_id)" class="text-blue-600 hover:underline">
                                                #{{ usage.order_id }}
                                            </Link>
                                        </td>
                                        <td class="py-4 text-sm font-black text-red-500">
                                            -{{ formatPrice(usage.discount_amount) }}
                                        </td>
                                        <td class="py-4 text-right text-xs text-gray-500 font-medium">
                                            {{ formatDateTime(usage.used_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="voucher.usages?.length === 0">
                                        <td colspan="4" class="py-10 text-center text-gray-400 italic">Chưa có lượt sử dụng nào.</td>
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