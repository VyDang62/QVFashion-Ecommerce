<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import {ref, watch} from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    roles: Array,
});

const getPermissionPreview = (permissions) => {
    if (permissions.length === 0) return 'Chưa có quyền';
    if (permissions.length <= 5) {
        return permissions.map(p => p.name.split('.')[1]).join(', ');
    }
    return permissions.slice(0, 5).map(p => p.name.split('.')[1]).join(', ') + '...';
};

const tableHeaders = ['Tên vai trò','Quyền hạn', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);

watch([searchTerm,perPage], debounce(([newSearch, newPerPage]) => {
    router.get(route('admin.roles.index'), { search: newSearch, perPage: newPerPage }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));
</script>

<template>
    <Head title="Quản lý vai trò" />

    <AdminLayout>
        <PageBreadcrumb pageTitle="Quản lý vai trò"/>
        <div class="space-y-6">
            <div class="flex items-center justify-end">
                <Link
                    v-if="can('roles.create')" 
                    :href="route('admin.roles.create')" 
                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM QUYỀN
                </Link>
            </div>
            <DataTable 
                title="Danh sách vai trò"
                :headers="tableHeaders"
                :items="roles?.data"
                :pagination="roles"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4 font-medium text-gray-800 text-md">{{ item.name }}</td>
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1">
                            <span 
                                v-for="perm in item.permissions?.slice(0, 3)" 
                                :key="perm.id"
                                class="px-2 py-0.5 text-sm font-medium bg-blue-50 text-blue-600 rounded-full"
                            >
                                {{ perm.name }}
                            </span>
                            
                            <span v-if="(item.permissions?.length || 0) > 3" class="text-sm text-gray-400 self-center">
                                +{{ item.permissions.length - 3 }} quyền khác
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <Link v-if="can('roles.edit') && item.name !== 'super-admin'"  :href="route('admin.roles.edit', item.id)" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                            </Link>
                            <DeleteAction v-if="can('roles.delete') && item.name !== 'super-admin'" :message="`Bạn có muốn xóa vai trò ${item.name}?. Dữ liệu sẽ bị xóa vĩnh viễn!`" :item="item" routeName="admin.roles.destroy" :displayName="item.name" />
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>