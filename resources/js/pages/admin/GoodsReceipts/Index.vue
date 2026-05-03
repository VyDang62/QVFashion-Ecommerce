<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import { usePermission } from '@/composables/usePermission';
import Badge from '@/components/admin/ui/Badge.vue';
const {can} = usePermission();
const props = defineProps({
    receipts: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 10 , status: ''})
    }
});

const tableHeaders = ['Mã phiếu', 'Nhà cung cấp', 'Ngày nhập', 'Tổng tiền', 'Trạng thái', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);
const statusFilter = ref(props.filters?.status || '');

watch([searchTerm, perPage, statusFilter], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.goodsreceipts.index'), 
        { search: newSearch, perPage: newPerPage, status: newStatus }, 
        {
            preserveState: true,
            replace: true,
            preserveScroll: true
        }
    );
}, 500));

const statusOptions = [
    { name: 'Tất cả trạng thái', id: '' },
    { name: 'Chờ xử lý', id: 'pending' },
    { name: 'Đã hoàn thành', id: 'completed' },
    { name: 'Đã hủy', id: 'cancelled' }
];

const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(price);
}

const getStatusClass = (status) => {
    const classes = {
        'pending': 'warning',
        'completed': 'success',
        'cancelled': 'error'
    };
    return classes[status] || 'light';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Chờ xử lý',
        'completed': 'Đã nhập kho',
        'cancelled': 'Đã hủy'
    };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Phiếu nhập hàng" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Phiếu nhập hàng" />
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <SingleSelect
                    v-model="statusFilter"
                    :options="statusOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="Tất cả trạng thái"
                    clearable="false"
                    class="w-60"
                />
                <Link
                    v-if="can('goods-receipts.create')"
                    :href="route('admin.goodsreceipts.create')" 
                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM PHIẾU NHẬP
                </Link>
            </div>
            <DataTable 
                title="Danh sách phiếu nhập"
                :headers="tableHeaders"
                :items="receipts?.data"
                :pagination="receipts"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo mã phiếu hoặc NCC..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4 font-bold text-blue-600 text-sm">{{ item.receipt_code }}</td>
                    <td class="px-5 py-4 text-sm">
                        <div class="font-medium text-gray-800">{{ item.supplier?.supplier_name }}</div>
                        <div class="text-xs text-gray-400">Người lập: {{ item.user?.name }}</div>
                    </td>
                    <td class="px-5 py-4 text-gray-600 text-sm">
                        {{ new Date(item.created_at).toLocaleDateString('vi-VN') }}
                    </td>

                    <td class="px-5 py-4 font-bold text-gray-800 text-sm">
                        {{ formatPrice(item.total_cost) }}
                    </td>

                    <td class="px-5 py-4">
                        <Badge variant="solid" size="sm" :color="getStatusClass(item.receipt_status)">
                             {{ getStatusLabel(item.receipt_status) }}
                        </Badge>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <Link v-if="can('goods-receipts.edit')" :href="route('admin.goodsreceipts.edit', item.id)" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                            </Link>
                            <DeleteAction
                                v-if="item.receipt_status === 'pending' && can('goods-receipts.delete')"
                                :item="item"
                                routeName="admin.goodsreceipts.destroy"
                                :displayName="item.receipt_code"    
                            />

                            <span v-else>
                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="1.5"/></svg>
                            </span>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>