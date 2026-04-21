<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useFormatter } from '@/composables/useFormatter';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import { usePermission } from '@/composables/usePermission';
import Badge from '@/components/admin/ui/Badge.vue';
const {can} = usePermission();
const props = defineProps({
    vouchers: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '', perPage: 10, status: 'active' }) }
});

const status = ref(props.filters.status || 'active');
const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);

const tableHeaders = ['Mã / Loại', 'Giá trị giảm', 'Sử dụng', 'Thời hạn', 'Hoạt động', 'Thao tác'];

watch([searchTerm, perPage, status], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.vouchers.index'), { 
        search: newSearch, 
        perPage: newPerPage, 
        status: newStatus 
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 500));

const handleRestore = (id) => {
    router.post(route('admin.vouchers.restore', id), {}, { preserveScroll: true });
};

const handleForceDelete = (id) => {
    router.delete(route('admin.vouchers.forceDelete', id), {}, { preserveScroll: true });
}

const getUsagePercentage = (used, limit) => {
    if (!limit) return 0;
    return Math.min(Math.round((used / limit) * 100), 100);
};

const {formatPrice, formatDateTime} = useFormatter();

</script>

<template>
    <Head title="Quản lý Mã giảm giá" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Mã giảm giá" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex gap-6">
                    <button @click="status = 'active'" 
                        :class="status === 'active' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                        class="pb-4 px-1 border-b-2 font-bold text-sm transition-all">
                        Đang hoạt động
                    </button>
                    <button @click="status = 'trash'" 
                        :class="status === 'trash' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500'"
                        class="pb-4 px-1 border-b-2 font-bold text-sm transition-all">
                        Thùng rác
                    </button>
                </div>
                <Link v-if="status === 'active' && can('vouchers.create')" :href="route('admin.vouchers.create')" 
                      class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    TẠO MÃ GIẢM GIÁ
                </Link>
            </div>

            <DataTable 
                :title="status === 'trash' ? 'Mã giảm giá đã tạm xóa' : 'Danh sách mã giảm giá'"
                :headers="tableHeaders" 
                :items="vouchers?.data" 
                :pagination="vouchers" 
                v-model:search="searchTerm" 
                v-model:per-page="perPage"
                searchPlaceholder="Tìm mã giảm giá..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <p class="font-bold text-gray-800 text-md uppercase">{{ item.code }}</p>
                        <p v-if="item.voucher_type === 'fixed'" class="text-xs text-gray-600 uppercase font-medium">Số tiền cố định</p>
                        <p v-else class="text-xs text-gray-600 uppercase font-medium">Phần trăm</p>
                    </td>

                    <td class="px-5 py-4">
                        <span class="font-bold text-md" :class="item.voucher_type === 'percentage' ? 'text-orange-600' : 'text-blue-600'">
                            {{ item.voucher_type === 'percentage' ? item.discount_value + '%' : formatPrice(item.discount_value) }}
                        </span>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex flex-col gap-1 w-32">
                            <div class="flex justify-between text-xs font-bold text-gray-500">
                                <span>{{ item.used_count }}/{{ item.usage_limit || '∞' }}</span>
                                <span>{{ getUsagePercentage(item.used_count, item.usage_limit) }}%</span>
                            </div>
                            <div class="h-1.5 w-full bg-gray-100 text-xs rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 transition-all" :style="{ width: getUsagePercentage(item.used_count, item.usage_limit) + '%' }"></div>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-sm space-y-1">
                            <p class="text-gray-600 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                {{ formatDateTime(item.start_date) }}
                            </p>
                            <p class="text-gray-600 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                                {{ formatDateTime(item.end_date) }}
                            </p>
                    </td>

                    <td class="px-5 py-4">
                        <Badge variant="solid" size="sm" :color="item.is_active ? 'success' : 'error'">
                            {{ item.is_active ? 'Đang bật' : 'Đang tắt' }}
                        </Badge>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <template v-if="status === 'active'">
                                <Link v-if="can('vouchers.view')" :href="route('admin.vouchers.show', item.id)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </Link>
                                <Link v-if="can('vouchers.edit')" :href="route('admin.vouchers.edit', item.id)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                                </Link>
                                <DeleteAction v-if="can('vouchers.delete')" :message="`Tạm xóa mã ${item.code}?`" :item="item" routeName="admin.vouchers.destroy" :displayName="item.code" />
                            </template>
                            
                            <template v-else>
                                <ConfirmAction
                                    v-if="can('vouchers.delete')" title="Khôi phục mã giảm giá" :message="`Bạn có muốn khôi phục lại mã giảm giá này?`" variant="primary" confirm-text="Khôi phục"@confirm="handleRestore(item.id)">
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-green-600 transition-colors" title="Khôi phục">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </template>
                                </ConfirmAction>
                                <ConfirmAction
                                    v-if="can('vouchers.delete')" title="Xóa vĩnh viễn" message="Dữ liệu mã giảm giá này sẽ mất vĩnh viễn!" variant="danger" confirm-text="Xóa vĩnh viễn" @confirm="handleForceDelete(item.id)">
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-red-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </template>
                                </ConfirmAction>
                            </template>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>