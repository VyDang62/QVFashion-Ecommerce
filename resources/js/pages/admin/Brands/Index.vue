<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    brands: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 10 })
    }
});

const tableHeaders = ['Thương hiệu', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);

watch([searchTerm,perPage], debounce(([newSearch, newPerPage]) => {
    router.get(route('admin.brands.index'), { search: newSearch, perPage: newPerPage }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));
</script>

<template>
    <Head title="Quản lý thương hiệu" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Thương hiệu" />
        <div class="space-y-6">
            <div class="flex items-center justify-end">
                <Link
                    v-if="can('brands.create')"
                    :href="route('admin.brands.create')" 
                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM THƯƠNG HIỆU
                </Link>
            </div>
            <DataTable 
                title="Thương hiệu"
                :headers="tableHeaders"
                :items="brands?.data"
                :pagination="brands"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4 font-medium text-gray-800 text-md">{{ item.brand_name || 'N/A' }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <Link v-if="can('brands.edit')" :href="route('admin.brands.edit', item.id)" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                            </Link>
                            <DeleteAction
                                v-if="can('brands.delete')"
                                :item="item"
                                routeName="admin.brands.destroy"
                                :displayName="item.brand_name"    
                            />
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>