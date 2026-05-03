<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import { usePermission } from '@/composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    attributes: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 10, status: 'active' })
    }
});

const tableHeaders = ['Tên thuộc tính', 'Các giá trị', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);
const status = ref(props.filters?.status || 'active');

watch([searchTerm, perPage, status], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.attributes.index'), { 
        search: newSearch, 
        perPage: newPerPage, 
        status: newStatus 
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));

const handleRestore = (id) => {
    router.post(route('admin.attributes.restore', id), {}, {
        preserveScroll: true,
    });
};

const handleForceDelete = (id) => {
    router.delete(route('admin.attributes.forcedelete', id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Quản lý thuộc tính" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Thuộc tính sản phẩm" />
        
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex gap-6">
                    <button 
                        @click="status = 'active'"
                        :class="status === 'active' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-4 px-1 border-b-2 font-bold text-sm transition-all"
                    >
                        Đang hoạt động
                    </button>
                    <button 
                        @click="status = 'trash'"
                        :class="status === 'trash' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-4 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2"
                    >
                        Ngừng hoạt động
                    </button>
                </div>

                <Link v-if="status === 'active' && can('attributes.create')" 
                    :href="route('admin.attributes.create')" 
                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM THUỘC TÍNH
                </Link>
            </div>

            <DataTable 
                :title="status === 'active' ? 'Danh sách thuộc tính' : 'Thuộc tính đã xóa'"
                :headers="tableHeaders"
                :items="attributes?.data"
                :pagination="attributes"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên thuộc tính..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <span class="font-bold text-gray-800 text-sm italic" v-if="status === 'trash'">[Đã xóa]</span>
                        <span class="font-bold text-gray-800 text-sm ml-1">{{ item.attribute_name }}</span>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            <template v-if="item.values && item.values.length > 0">
                                <span v-for="val in item.values" :key="val.id" 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-white text-gray-600 border border-gray-100 shadow-sm transition-all"
                                >
                                    <span 
                                        v-if="val.hex_code" 
                                        class="w-3 h-3 rounded-full border border-gray-200" 
                                        :style="{ backgroundColor: val.hex_code }"
                                    ></span>
                                    {{ val.value }}
                                </span>
                            </template>
                            <span v-else class="text-xs text-gray-400 italic">Trống</span>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <template v-if="status === 'active'">
                                <Link v-if="can('attributes.edit')" :href="route('admin.attributes.edit', item.id)" 
                                      class="text-gray-400 hover:text-blue-600 transition-colors" title="Chỉnh sửa">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                                </Link>

                                <DeleteAction
                                    v-if="can('attributes.delete')"
                                    :item="item"
                                    :message="`Bạn có chắc chắn muốn tạm xóa thuộc tính ${item.attribute_name}?`"
                                    routeName="admin.attributes.destroy"
                                    :displayName="item.attribute_name"    
                                />
                            </template>

                            <template v-else>
                                <ConfirmAction
                                    v-if="can('attributes.delete')"
                                    title="Khôi phục thuộc tính"
                                    :message="`Bạn có muốn khôi phục thuộc tính ${item.attribute_name} và tất cả các giá trị đi kèm không?`"
                                    variant="primary"
                                    confirm-text="Khôi phục ngay"
                                    @confirm="handleRestore(item.id)"
                                >
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-green-600 transition-colors" title="Khôi phục">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </template>
                                </ConfirmAction>

                                <ConfirmAction
                                    v-if="can('attributes.delete')"
                                    title="Xóa vĩnh viễn"
                                    :message="`Cảnh báo: Xóa vĩnh viễn thuộc tính ${item.attribute_name} sẽ làm mất dữ liệu lịch sử của các biến thể sản phẩm liên quan. Bạn chắc chắn chứ?`"
                                    variant="danger"
                                    confirm-text="Xóa vĩnh viễn"
                                    @confirm="handleForceDelete(item.id)"
                                >
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-red-600 transition-colors" title="Xóa vĩnh viễn">
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