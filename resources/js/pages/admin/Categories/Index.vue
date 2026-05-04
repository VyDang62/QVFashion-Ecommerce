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
import Badge from '@/components/admin/ui/Badge.vue';
const {can} = usePermission();
const props = defineProps({
    categories: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 10 })
    }
});

const tableHeaders = ['ID','Danh mục','Cấp độ','Danh mục cha','Giới tính','Loại sản phẩm', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);
const status = ref(props.filters?.status || 'active');

watch([searchTerm, perPage, status], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.categories.index'), { 
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
    router.post(route('admin.categories.restore', id), {}, {
        preserveScroll: true,
    });
};

const handleForceDelete = (id) => {
    router.delete(route('admin.categories.forcedelete', id), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Quản lý danh mục sản phẩm" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Danh mục sản phẩm" />
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
                <Link 
                    v-if="status === 'active' && can('categories.create')" 
                    :href="route('admin.categories.create')" 
                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM DANH MỤC
                </Link>
            </div>
            
            <DataTable 
                title="Danh mục giới tính"
                :headers="tableHeaders"
                :items="categories?.data"
                :pagination="categories"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên..."
                
            >
            
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <p class="text-sm text-gray-800 font-medium">#{{item.id}}</p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center">
                            <span v-if="item.parent_id" class="text-gray-400 mr-2">-</span>
                            <span :class="{'font-semibold text-gray-800': !item.parent_id, 'text-gray-600': item.parent_id}">{{item.category_name}}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <Badge v-if="!item.parent_id" variant="light" size="sm" color="pink">
                            Cấp 1
                        </Badge>
                        <Badge v-else variant="light" size="sm" color="primary">
                            Cấp 2
                        </Badge>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500 italic">{{item.parent?.category_name || 'Không có'}}</td>
                    <td class="px-5 py-4 text-sm text-gray-500">
                        <td class="px-5 py-4 text-sm text-gray-500">
                            <Badge v-if="item.gender === 'female'" variant="solid" size="sm" color="pink">
                                Nữ
                            </Badge>
                            <Badge v-else-if="item.gender === 'male'" variant="solid" size="sm" color="primary">
                                Nam
                            </Badge>
                        </td>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{item.product_type?.type_name || 'Không có'}}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <template v-if="status === 'active'">
                                <Link v-if="can('categories.edit')" :href="route('admin.categories.edit', item.id)" class="text-gray-400 hover:text-blue-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                                </Link>
                                <DeleteAction
                                    v-if="can('categories.delete')"
                                    :message="`Bạn có chắc chắn muốn tạm xóa danh mục ${item.category_name}?`"
                                    :item="item"
                                    routeName="admin.categories.destroy"
                                    :displayName="item.category_name"    
                                />
                            </template>
                            <template v-else>
                                <ConfirmAction
                                    v-if="can('categories.delete')"
                                    title="Khôi phục danh mục"
                                    :message="`Bạn có muốn khôi phục lại danh mục ${item.category_name}?`"
                                    variant="primary"
                                    confirm-text="Khôi phục"
                                    @confirm="handleRestore(item.id)"
                                >
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-green-600 transition-colors" title="Khôi phục">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </template>
                                </ConfirmAction>

                                <ConfirmAction
                                    v-if="can('categories.delete')"
                                    title="Xóa vĩnh viễn"
                                    :message="`Bạn có muốn xóa vĩnh viễn danh mục ${item.category_name}?`"
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