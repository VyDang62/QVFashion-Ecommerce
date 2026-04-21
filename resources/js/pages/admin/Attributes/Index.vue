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
    attributes: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 10 })
    }
});

const tableHeaders = ['Tên thuộc tính', 'Các giá trị', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);

watch([searchTerm, perPage], debounce(([newSearch, newPerPage]) => {
    router.get(route('admin.attributes.index'), { search: newSearch, perPage: newPerPage }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));

</script>

<template>
    <Head title="Quản lý thuộc tính" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Thuộc tính sản phẩm" />
        
        <div class="space-y-6">
            <div class="flex items-center justify-end">
                <Link
                    v-if="can('attributes.create')"
                    :href="route('admin.attributes.create')" 
                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM THUỘC TÍNH
                </Link>
            </div>

            <DataTable 
                title="Danh sách thuộc tính"
                :headers="tableHeaders"
                :items="attributes?.data"
                :pagination="attributes"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên thuộc tính..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <span class="font-bold text-gray-800 text-sm">
                            {{ item.attribute_name }}
                        </span>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            <template v-if="item.values && item.values.length > 0">
                                <span v-for="val in item.values" :key="val.id" 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-white text-gray-700 border border-gray-200 shadow-sm hover:border-gray-300 transition-colors"
                                >
                                    <span 
                                        v-if="val.hex_code" 
                                        class="w-3.5 h-3.5 rounded-full border border-gray-200 shadow-inner" 
                                        :style="{ backgroundColor: val.hex_code }"
                                        :title="val.hex_code"
                                    ></span>
                                    
                                    {{ val.value }}
                                </span>
                            </template>
                            <span v-else class="text-xs text-gray-400 italic">Chưa có giá trị</span>
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <Link v-if="can('attributes.edit')" :href="route('admin.attributes.edit', item.id)" 
                                  class="text-gray-400 hover:text-blue-600 transition-colors"
                                  title="Chỉnh sửa">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/>
                                </svg>
                            </Link>

                            <DeleteAction
                                v-if="can('attributes.delete')"
                                :item="item"
                                routeName="admin.attributes.destroy"
                                :displayName="item.attribute_name"    
                            />
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>