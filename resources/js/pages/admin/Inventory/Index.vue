<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import { useFormatter } from '@/composables/useFormatter';
import Badge from '@/components/admin/ui/Badge.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    batches: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '', perPage: 10, status: '' }) }
});

const { formatPrice, formatDate } = useFormatter();

const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);
const statusFilter = ref(props.filters.status || '');

const tableHeaders = ['Mã Lô / Ngày Nhập', 'Sản phẩm', 'Giá nhập', 'Tồn kho', 'Nhà cung cấp', 'Tình trạng', 'Thao tác'];

watch([searchTerm, perPage, statusFilter], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.inventory.batches'), 
        { search: newSearch, perPage: newPerPage, status: newStatus }, 
        { preserveState: true, replace: true, preserveScroll: true }
    );
}, 500));

const statusOptions = [
    { name: 'Tất cả tình trạng', id: '' },
    { name: 'Đã hết hàng', id: 'out_of_stock' },
    { name: 'Sắp hết hàng', id: 'low_stock' },
    { name: 'Còn hàng', id: 'in_stock' },
];

const getBatchStatus = (batch) => {
    if (batch.remaining_quantity === 0) return { label: 'Hết hàng', color: 'error' };
    if (batch.remaining_quantity < (batch.original_quantity * 0.2)) return { label: 'Sắp hết', color: 'warning' };
    return { label: 'Còn hàng', color: 'success' };
};

</script>

<template>
    <Head title="Quản lý lô hàng" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Quản lý lô hàng" />
        
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <SingleSelect
                    v-model="statusFilter"
                    :options="statusOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="Lọc theo tình trạng"
                    class="w-64"
                />
            </div>

            <DataTable 
                title="Danh sách lô hàng"
                :headers="tableHeaders"
                :items="batches?.data"
                :pagination="batches"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo mã lô hoặc tên sản phẩm..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <div class="font-bold text-gray-800 text-sm">{{ item.batch_code }}</div>
                        <div class="text-xs text-gray-500">{{ formatDate(item.received_date) }}</div>
                    </td>
                    
                    <td class="px-5 py-4">
                        <div class="text-sm font-medium text-gray-800 truncate max-w-[200px]" :title="item.variant?.product?.product_name">
                            {{ item.variant?.product?.product_name }}
                        </div>
                        <div class="text-xs text-blue-600 font-mono">{{ item.variant?.sku }}</div>
                    </td>

                    <td class="px-5 py-4 font-bold text-emerald-600 text-sm">
                        {{ formatPrice(item.purchase_price) }}
                    </td>

                    <td class="px-5 py-4 text-sm text-center">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900">{{ item.remaining_quantity }}</span>
                            <span class="text-xs text-gray-800 uppercase font-medium">Gốc: {{ item.original_quantity }}</span>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ item.receipt?.supplier?.supplier_name || 'N/A' }}
                    </td>

                    <td class="px-5 py-4">
                        <Badge variant="solid" size="sm" :color="getBatchStatus(item).color">
                            {{ getBatchStatus(item).label }}
                        </Badge>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <Link v-if="can('inventory.edit')" :href="route('admin.inventory.batches.edit', item.id)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                            </Link>

                            <Link v-if="can('goods-receipts.view')" :href="route('admin.goodsreceipts.edit', item.goods_receipt_id)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Xem phiếu nhập">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </Link>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>