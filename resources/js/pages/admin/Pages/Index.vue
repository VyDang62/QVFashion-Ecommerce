<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import { useFormatter } from '@/composables/useFormatter';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    pages: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '', perPage: 10, status: 'active' }) }
});

const status = ref(props.filters.status || 'active');
const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);

const tableHeaders = ['Tiêu đề trang', 'Đường dẫn (Slug)', 'Trạng thái', 'Ngày tạo', 'Thao tác'];

watch([searchTerm, perPage, status], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.pages.index'), { 
        search: newSearch, 
        perPage: newPerPage, 
        status: newStatus 
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 500));

const handleRestore = (id) => {
    router.post(route('admin.pages.restore', id), {}, {
        preserveScroll: true,
    });
};

const handleForceDelete = (id) => {
    router.delete(route('admin.pages.force-delete', id), {}, {
        preserveScroll: true,
    });
}

const {formatDate} = useFormatter();
</script>

<template>
    <Head title="Quản lý trang tĩnh" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Trang tĩnh" />

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
                        Thùng rác
                    </button>
                </div>

                <Link v-if="status === 'active' && can('pages.create')" :href="route('admin.pages.create')" 
                      class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM TRANG MỚI
                </Link>
            </div>

            <DataTable 
                :title="status === 'trash' ? 'Danh sách trang đã xóa' : 'Danh sách trang tĩnh'"
                :headers="tableHeaders" 
                :items="pages?.data" 
                :pagination="pages" 
                v-model:search="searchTerm" 
                v-model:per-page="perPage"
                searchPlaceholder="Tìm tiêu đề hoặc nội dung..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-800 text-md">{{ item.title }}</span>
                            <span v-if="item.meta_title" class="text-xs text-gray-600 truncate max-w-[200px]">SEO: {{ item.meta_title }}</span>
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <code class="text-sm bg-gray-100 text-blue-700 px-2 py-1 rounded">/page/{{ item.slug }}</code>
                    </td>

                    <td class="px-5 py-4">
                        <Badge variant="solid" size="sm" :color="item.is_active ? 'success' : 'error'">
                            {{ item.is_active ? 'Công khai' : 'Không công khai' }}
                        </Badge>
                    </td>

                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ formatDate(item.created_at) }}
                    </td>

                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <template v-if="status === 'active'">
                                <Link v-if="can('pages.edit')" :href="route('admin.pages.edit', item.id)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Chỉnh sửa">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                                </Link>
                                
                                <DeleteAction
                                    v-if="can('pages.delete')"
                                    :message="`Bạn có muốn chuyển trang '${item.title}' vào thùng rác?`" 
                                    :item="item" 
                                    routeName="admin.pages.destroy" 
                                    :displayName="item.title" 
                                />
                            </template>
                            
                            <template v-else>
                                <ConfirmAction
                                    v-if="can('pages.delete')"
                                    title="Khôi phục trang"
                                    :message="`Bạn có muốn khôi phục lại trang '${item.title}'?`"
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
                                    v-if="can('pages.delete')"
                                    title="Xóa vĩnh viễn"
                                    message="Hành động này không thể hoàn tác. Trang này sẽ bị xóa vĩnh viễn khỏi cơ sở dữ liệu!"
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