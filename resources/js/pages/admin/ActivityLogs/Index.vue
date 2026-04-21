<script setup>
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import { useFormatter } from '@/composables/useFormatter';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import DataTable from '@/components/admin/tables/DataTable.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import {Head} from '@inertiajs/vue3';
import Badge from '@/components/admin/ui/Badge.vue';
const props = defineProps({
    activityLogs: Object,
    filters: Object,
});

const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);
const actionFilter = ref(props.filters?.action || '');

const actionOptions = [
    { name: 'Tất cả hành động', id: '' },
    { name: 'Tạo', id: 'create' },
    { name: 'Cập nhật', id: 'update' },
    { name: 'Duyệt', id: 'approve' },
    { name: 'Hủy', id: 'cancel' },
];

watch([searchTerm, perPage, actionFilter], debounce(([newSearch, newPerPage, newAction]) => {
    router.get(route('admin.activitylogs.index'), 
        { search: newSearch, perPage: newPerPage, action: newAction }, 
        {
            preserveState: true,
            replace: true,
            preserveScroll: true
        }
    );
}, 500));

const getActionClass = (action) => {
    if (!action) return 'bg-gray-100 text-gray-600';
    
    const act = action.toLowerCase();

    if (act.includes('approve') || act.includes('restore') || act.includes('active') || act.includes('confirm')) {
        return 'success';
    }
    
    if (act.includes('delete') || act.includes('cancel') || act.includes('destroy') || act.includes('remove')) {
        return 'error';
    }
    
    if (act.includes('create') || act.includes('store') || act.includes('add')) {
        return 'primary';
    }
    
    if (act.includes('update') || act.includes('edit') || act.includes('modify') || act.includes('adjust')) {
        return 'warning';
    }

    return 'light';
};
const tableHeaders = ['Thời gian','Nhân viên','Hành động','Nội dung', 'IP'];
const {formatDateTime} = useFormatter();
</script>

<template>
    <AdminLayout>
        <Head title="Nhật ký hệ thống"></Head>
        <PageBreadcrumb pageTitle="Nhật ký hệ thống" />
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <SingleSelect
                    v-model="actionFilter"
                    :options="actionOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="Tất cả hành động"
                    clearable="false"
                    class="w-60"
                />
            </div>
            <DataTable 
                title="Nhật ký hệ thống"
                :headers="tableHeaders"
                :items="activityLogs?.data"
                :pagination="activityLogs"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo mô tả hoặc tên nhân viên..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4 font-medium text-gray-800 text-sm">{{formatDateTime(item?.created_at)}}</td>
                    <td class="px-5 py-4">
                        <div class="font-medium text-gray-800 text-sm">
                            {{item?.user?.full_name}}
                        </div>
                        <div class="text-gray-400 text-sm ">
                            {{item?.user?.email}}
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <Badge :color="getActionClass(item?.action)" variant="solid" size="sm">{{item?.action}}</Badge>
                    </td>
                    <td class="px-5 py-4 text-gray-600 text-sm">
                        {{item?.description}}
                        <div v-if="item?.properties" class="mt-1">
                            <details class="text-xs text-blue-600 cursor-pointer">
                                <summary>Xem chi tiết dữ liệu</summary>
                                <pre class="bg-gray-100 p-2 mt-2 rounded text-gray-600 overflow-auto max-w-xs">{{ item?.properties }}</pre>
                            </details>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-600">
                        {{item?.ip_address}}
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>