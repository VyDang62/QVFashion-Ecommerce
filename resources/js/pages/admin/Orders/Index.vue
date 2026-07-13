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
    orders: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '', perPage: 10, status: '' }) }
});

const { formatPrice, formatDate } = useFormatter();

const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);
const statusFilter = ref(props.filters.status !== null ? props.filters.status.toString() : '');

const tableHeaders = ['ID / Mã đơn', 'Khách hàng', 'Ngày đặt', 'Tổng tiền', 'P.Thức', 'Trạng thái', 'Thao tác'];

watch([searchTerm, perPage, statusFilter], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.orders.index'), 
        { search: newSearch, perPage: newPerPage, status: newStatus }, 
        { preserveState: true, replace: true, preserveScroll: true }
    );
}, 500));

const statusOptions = [
    { name: 'Tất cả trạng thái', id: '' },
    { name: 'Đã hủy', id: '0' },
    { name: 'Chờ xử lý (COD)', id: '1' },
    { name: 'Chờ thanh toán', id: '2' },
    { name: 'Đã thanh toán', id: '3' },
    { name: 'Đang giao hàng', id: '4' },
    { name: 'Đã giao hàng', id: '5' },
    { name: 'Hoàn thành', id: '6' },
    { name: 'Yêu cầu trả hàng', id: '7' },
    { name: 'Đang thu hồi hàng', id: '8' },
    { name: 'Đã nhận hàng trả', id: '9' },
    { name: 'Đã hoàn tiền', id: '10' },
];
</script>

<template>
    <Head title="Quản lý đơn hàng" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Quản lý đơn hàng" />
        
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <SingleSelect
                    v-model="statusFilter"
                    :options="statusOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="Tất cả trạng thái"
                    class="w-64"
                />
            </div>

            <DataTable 
                title="Danh sách đơn hàng"
                :headers="tableHeaders"
                :items="orders?.data"
                :pagination="orders"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo ID, mã đơn hoặc khách..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <div class="font-bold text-blue-600 text-sm">#{{ item.id }}</div>
                        <div class="text-sm text-gray-600 " :title="item.order_code">
                            {{ item.order_code }}
                        </div>
                    </td>
                    
                    <td class="px-5 py-4">
                        <div class="text-sm font-medium text-gray-800">{{ item.user?.full_name}}</div>
                        <div class="text-xs text-gray-600">{{ item.shipping_phone_number }}</div>
                    </td>

                    <td class="px-5 py-4 text-gray-800 text-sm">
                        {{ formatDate(item.created_at) }}
                    </td>

                    <td class="px-5 py-4 font-bold text-gray-900 text-sm">
                        {{ formatPrice(item.final_amount) }}
                    </td>

                    <td class="px-5 py-4">
                        <span class="text-xs font-bold uppercase text-gray-800 px-1.5 py-0.5 rounded">
                            {{ item.payment_method }}
                        </span>
                    </td>

                    <td class="px-5 py-4">
                        <Badge variant="solid" size="sm" :color="item.status_info.badge_admin">{{ item.status_info.label}} </Badge>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <Link v-if="can('orders.view')" :href="route('admin.orders.show', item.id)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Xem chi tiết">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </Link>
                            <Link v-if="(item.order_status !== 0 && item.order_status !== 6 && item.order_status !== 2 && item.order_status !== 10) && can('orders.edit')" :href="route('admin.orders.edit', item.id)" class="text-gray-400 hover:text-orange-600 transition-colors" title="Cập nhật trạng thái">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/>
                                </svg>
                            </Link>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>