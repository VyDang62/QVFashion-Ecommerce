<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import { useFormatter} from '@/composables/useFormatter';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 10 })
    },
    auth: Object,
});

const tableHeaders = ['ID','Người dùng','Vai trò','Ngày tham gia', 'Thao tác'];
const searchTerm = ref(props.filters?.search || '');
const perPage = ref(props.filters?.perPage || 10);
const status = ref(props.filters?.status || 'active');
const selectedRole = ref(props.filters?.role || '');

const roleOptions = [
    { id: '', name: 'Tất cả vai trò' },
    { id: 'super-admin', name: 'Super Admin' },
    { id: 'customer', name: 'Khách hàng' },
    { id: 'sales', name: 'Nhân viên bán hàng' },
    { id: 'warehouse-manager', name: 'Quản lý kho' },
];

watch([searchTerm, perPage, status, selectedRole], debounce(([newSearch, newPerPage, newStatus, newRole]) => {
    router.get(route('admin.users.index'), { 
        search: newSearch, 
        perPage: newPerPage, 
        status: newStatus,
        role: newRole
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));

const isSuperAdmin = (user) => {
    return user.roles?.some(role => role.name === 'super-admin');
};

const canManageTarget = (targetUser) => {
    const currentUser = props.auth.user;
    if (isSuperAdmin(targetUser)) {
        return isSuperAdmin(currentUser);
    }
    return true;
};

const {formatDate} = useFormatter();
const getRoleBadge = (roleName) => {
    const styles = {
        'super-admin': 'error',
        'customer': 'success',
        'sales': 'warning',
        'warehouse-manager': 'primary',
    };
    return styles[roleName] || 'light';
};
const getRoleLabel = (roleName) => {
    const labels = {
        'super-admin': 'Super Admin',
        'admin': 'Quản trị viên',
        'customer': 'Khách hàng',
        'sales staff': 'Nhân viên bán hàng',
        'warehouse-manager': 'Quản lý kho',
    };
    return labels[roleName] || roleName;
};
const toggleStatus = (user) => {
    router.patch(route('admin.users.togglestatus', user.id), {}, {
        preserveScroll: true,
    });
}
const handleRestore = (id) => {
    router.post(route('admin.users.restore', id), {}, {
        preserveScroll: true,
    });
};

const handleForceDelete = (id) => {
    router.delete(route('admin.users.forcedelete', id), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Quản lý người dùng" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Người dùng" />
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
                <div class="flex items-center gap-3 justify-between">
                    <SingleSelect
                        v-model="selectedRole"
                        :options="roleOptions"
                        option-label="name"
                        option-value="id"
                        placeholder="Lọc theo vai trò"
                        class="w-48"
                    />
                    <Link v-if="status === 'active' && can('users.create')" :href="route('admin.users.create')" 
                          class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                        THÊM MỚI
                    </Link>
                </div>
            </div>
            <DataTable 
                :title="status === 'trash' ? 'Danh sách người dùng đã xóa' : 'Danh sách người dùng'"
                :headers="tableHeaders"
                :items="users?.data"
                :pagination="users"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên hoặc email..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4 text-sm font-mono text-gray-400">#{{ item.id }}</td>

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div>
                                <p class="font-medium text-gray-800 text-md">{{ item.full_name }}</p>
                                <p class="text-sm text-gray-500">{{ item.email }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 ">
                        <div class="flex gap-1">
                            <Badge 
                                v-for="role in item.roles" 
                                :key="role.id" 
                                variant="solid" 
                                size="sm" 
                                :color="getRoleBadge(role.name)"
                            >
                                {{ getRoleLabel(role.name) }}
                            </Badge>

                            <span v-if="!item.roles || item.roles.length === 0" class="text-sm text-gray-400 italic">
                                Chưa có vai trò
                            </span>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-sm text-gray-500">{{ formatDate(item.created_at) }}</td>

                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <template v-if="status === 'active'">
                                <Link v-if="can('users.view')" :href="route('admin.users.show', item.id)" class="text-gray-400 hover:text-blue-600" title="Xem chi tiết">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </Link>
                                <Link v-if="can('users.edit') && canManageTarget(item)" :href="route('admin.users.edit', item.id)" 
                                    class="text-gray-400 hover:text-blue-600 transition-colors"
                                    title="Chỉnh sửa">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </Link>

                                <template v-if="item.id !== auth.user.id">
                                    <DeleteAction
                                        v-if="can('users.delete') && canManageTarget(item)"
                                        :message="`Bạn có muốn tạm xóa người dùng ${item.full_name}?. Bạn có thể hoàn tác tại trang ngừng hoạt động!`"
                                        :item="item"
                                        route-name="admin.users.destroy"
                                        :display-name="item.full_name"
                                    />
                                </template>

                                <template v-if="item.id !== auth.user.id">
                                    <ConfirmAction
                                        v-if="can('users.edit') && canManageTarget(item)"
                                        :title="item.is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản'"
                                        :message="`Bạn có chắc chắn muốn ${item.is_active ? 'khóa' : 'mở khóa'} tài khoản của ${item.full_name}? Người dùng này sẽ ${item.is_active ? 'không thể' : 'có thể'} đăng nhập lại!`"
                                        :confirm-text="item.is_active ? 'Xác nhận khóa' : 'Mở khóa ngay'"
                                        :variant="item.is_active ? 'danger' : 'success'"
                                        @confirm="toggleStatus(item)"
                                    >
                                        <template #trigger>
                                            <button 
                                                class="flex items-center justify-center transition-colors" 
                                                :class="item.is_active ? 'text-gray-400 hover:text-amber-500' : 'text-amber-500 hover:text-green-600'" 
                                                :title="item.is_active ? 'Khóa' : 'Mở khóa'"
                                            >
                                                <svg v-if="item.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </template>
                                    </ConfirmAction>
                                </template>
                            </template>
                            <template v-else>
                                <ConfirmAction
                                    v-if="can('users.delete')"
                                    title="Khôi phục tài khoản"
                                    :message="`Bạn có muốn khôi phục lại quyền truy cập cho ${item.full_name}?`"
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
                                    v-if="can('users.delete')"
                                    title="Xóa vĩnh viễn"
                                    message="Hành động này không thể hoàn tác. Mọi dữ liệu của người dùng này sẽ bị xóa vĩnh viễn!"
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