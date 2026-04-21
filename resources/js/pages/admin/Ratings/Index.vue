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
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    ratings: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '', perPage: 10, status: '', score: '' }) }
});

const { formatDate } = useFormatter();

const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);
const statusFilter = ref(props.filters.status !== null ? props.filters.status.toString() : '');
const scoreFilter = ref(props.filters.score || '');

const tableHeaders = ['ID', 'Khách hàng', 'Sản phẩm', 'Đánh giá', 'Nội dung', 'Trạng thái', 'Thao tác'];

watch([searchTerm, perPage, statusFilter, scoreFilter], debounce(([newSearch, newPerPage, newStatus, newScore]) => {
    router.get(route('admin.ratings.index'), 
        { search: newSearch, perPage: newPerPage, status: newStatus, score: newScore }, 
        { preserveState: true, replace: true, preserveScroll: true }
    );
}, 500));

const statusOptions = [
    { name: 'Tất cả trạng thái', id: '' },
    { name: 'Chờ phê duyệt', id: '0' },
    { name: 'Đã phê duyệt', id: '1' },
];

const scoreOptions = [
    { name: 'Tất cả sao', id: '' },
    { name: '5 Sao', id: '5' },
    { name: '4 Sao', id: '4' },
    { name: '3 Sao', id: '3' },
    { name: '2 Sao', id: '2' },
    { name: '1 Sao', id: '1' },
];

//Hàm xóa & duyệt nhanh
const toggleApproval = (id) => {
    router.patch(route('admin.ratings.toggleapproval', id), {}, {
        preserveScroll: true,
    });
};

const handleDelete = (id) => {
    router.delete(route('admin.ratings.destroy', id), {}, {
        preserveScroll: true,
    });
}

const expandedItems = ref([]);

const toggleExpand = (id) => {
    if (expandedItems.value.includes(id)) {
        expandedItems.value = expandedItems.value.filter(itemId => itemId !== id);
    } else {
        expandedItems.value.push(id);
    }
};

const isExpanded = (id) => expandedItems.value.includes(id);
</script>

<template>
    <Head title="Quản lý đánh giá" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Quản lý đánh giá" />
        
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <SingleSelect
                    v-model="statusFilter"
                    :options="statusOptions"
                    option-label="name"
                    option-value="id"
                    class="w-48"
                />
                <SingleSelect
                    v-model="scoreFilter"
                    :options="scoreOptions"
                    option-label="name"
                    option-value="id"
                    class="w-48"
                />
            </div>

            <DataTable 
                title="Danh sách đánh giá từ khách hàng"
                :headers="tableHeaders"
                :items="ratings?.data"
                :pagination="ratings"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm tên khách, sản phẩm hoặc nội dung..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4 text-sm font-bold text-gray-600">
                        #{{ item.id }}
                    </td>
                    
                    <td class="px-5 py-4">
                        <div class="text-sm font-medium text-gray-800">{{ item.user?.full_name }}</div>
                        <div class="text-xs text-gray-500">{{ formatDate(item.created_at) }}</div>
                    </td>

                    <td class="px-5 py-4">
                        <div class="text-sm text-grey-800 font-medium line-clamp-1" :title="item.product?.product_name">
                            {{ item.product?.product_name }}
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-0.5">
                            <svg 
                                v-for="i in 5" 
                                :key="i"
                                xmlns="http://www.w3.org/2000/svg" 
                                viewBox="0 0 20 20" 
                                fill="currentColor" 
                                class="w-4 h-4 transition-colors duration-200"
                                :class="i <= item.score ? 'text-yellow-400' : 'text-gray-200'"
                            >
                                <path 
                                    fill-rule="evenodd" 
                                    d="M10.868 2.808c-.3-.921-1.603-.921-1.902 0l-1.358 4.177a.75.75 0 01-.712.519H2.493c-.969 0-1.371 1.24-.588 1.81l3.562 2.588a.75.75 0 01.273.84l-1.358 4.177c-.3.921.755 1.688 1.54 1.118l3.562-2.588a.75.75 0 01.874 0l3.562 2.588c.784.57 1.838-.197 1.539-1.118l-1.358-4.177a.75.75 0 01.273-.84l3.562-2.588c.783-.57.38-1.81-.588-1.81h-4.405a.75.75 0 01-.712-.519l-1.358-4.177z" 
                                    clip-rule="evenodd" 
                                />
                            </svg>
                        </div>
                    </td>

                    <td class="px-5 py-4 min-w-0">
                        <div class="w-64 flex flex-col items-start min-w-0">
                            <p :class="[
                                    'text-sm text-gray-600 w-full whitespace-normal break-words overflow-hidden', 
                                    isExpanded(item.id) ? '' : 'line-clamp-2'
                                ]"
                            >
                                {{ item.content }}
                            </p>

                            <button 
                                v-if="item.content && item.content.length > 50"
                                @click="toggleExpand(item.id)"
                                class="mt-1 text-[12px] font-bold text-grey-600 hover:text-blue-800 transition-colors flex items-center gap-1 shrink-0"
                            >
                                <span class="text-xs text-blue-600">{{ isExpanded(item.id) ? 'Thu gọn' : 'Xem thêm' }}</span>
                            </button>
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <Badge 
                            variant="solid" 
                            size="sm" 
                            :color="item.is_approved ? 'success' : 'warning'"
                        >
                            {{ item.is_approved ? 'Đã duyệt' : 'Chờ duyệt' }}
                        </Badge>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3 justify-begin">
                            <ConfirmAction
                                v-if="can('ratings.approve')"
                                title="Đổi trạng thái đánh giá" :message="item.is_approved ? `Bạn có muốn hủy phê duyệt đánh giá này!` : `Bạn có muốn phê duyệt đánh giá này!`" variant="primary" :confirm-text="item.is_approved ? `Hủy phê duyệt!` : `Phê duyệt!`" @confirm="toggleApproval(item.id)">
                                <template #trigger>
                                    <button class="text-gray-400 hover:text-green-600 transition-colors" title="Đổi trạng thái">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                </template>
                            </ConfirmAction>
                            <ConfirmAction
                                v-if="can('ratings.delete')"
                                title="Xóa vĩnh viễn"
                                message="Hành động này không thể hoàn tác. Đánh giá sẽ bị xóa khỏi máy chủ!"
                                variant="danger"
                                confirm-text="Xóa vĩnh viễn"
                                @confirm="handleDelete(item.id)"
                            >
                                <template #trigger>
                                    <button class="text-gray-400 hover:text-red-600 transition-colors" title="Xóa vĩnh viễn">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                </template>
                            </ConfirmAction>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>