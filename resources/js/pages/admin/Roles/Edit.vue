<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const props = defineProps({
    role: Object,
    permissionsGrouped: Object,
    rolePermissions: Array
});

const form = useForm({
    name: props.role?.name || '',
    permissions: props.rolePermissions || []
});

const formatAction = (name) => {
    const action = name.split('.')[1];
    const labels = {
        'view': 'Xem',
        'create': 'Thêm mới',
        'edit': 'Sửa',
        'delete': 'Xóa',
        'cancel': 'Hủy',
        'approve': 'Duyệt'
    };
    return labels[action] || action;
};

const toggleGroup = (modulePermissions, isChecked) => {
    modulePermissions.forEach(perm => {
        const index = form.permissions.indexOf(perm.name);
        if (isChecked) {
            if (index === -1) form.permissions.push(perm.name);
        } else {
            if (index !== -1) form.permissions.splice(index, 1);
        }
    });
};
const isGroupAllSelected = (modulePermissions) => {
    return modulePermissions.every(perm => form.permissions.includes(perm.name));
};

const protectedRoles = ['customer', 'super-admin', 'warehouse-manager', 'sales-staff'];

const isProtectedRole = computed(() => {
    return props.role && protectedRoles.includes(props.role.name);
});

const submit = () => {
    form.put(route('admin.roles.update', props.role.id));
};
</script>

<template>
    <Head :title="`Sửa vai trò: ${role.name}`" />

    <AdminLayout>
        <PageBreadcrumb 
            :pageTitle="`Sửa vai trò`" 
            parentName="Vai trò" 
            :parentRoute="route('admin.roles.index')" 
        />

        <form @submit.prevent="submit" class="space-y-6">
            <ComponentCard title="Thông tin cơ bản">
                <div class="max-w-md">
                    <InputLabel >Tên vai trò <span class="text-red-500">*</span></InputLabel>
                    <Input 
                        id="role_name"
                        v-model="form.name"
                        :disabled="isProtectedRole"
                        placeholder="Nhập tên vai trò (vd: kế toán, thủ kho...)" 
                        :error="form.errors.name" 
                    />
                </div>
            </ComponentCard>
            
            <ComponentCard title="Phân quyền chi tiết">
                <div class="space-y-6">
                    <div v-for="(perms, groupName) in permissionsGrouped" :key="groupName" 
                         class="border border-gray-100 rounded-xl overflow-hidden">
                        
                        <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border-b border-gray-100">
                            <span class="font-bold text-blue-600 uppercase text-xs tracking-wider">
                                {{ groupName }}
                            </span>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    :checked="isGroupAllSelected(perms)"
                                    @change="toggleGroup(perms, $event.target.checked)"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span class="text-xs font-medium text-gray-500 uppercase">Chọn tất cả</span>
                            </label>
                        </div>
                        
                        <div class="p-4 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div v-for="perm in perms" :key="perm.id" class="flex items-center gap-3 group">
                                <input 
                                    type="checkbox" 
                                    :id="'perm-' + perm.id"
                                    :value="perm.name" 
                                    v-model="form.permissions"
                                    class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                />
                                <label :for="'perm-' + perm.id" class="text-sm text-gray-700 cursor-pointer group-hover:text-blue-600 transition-colors capitalize">
                                    {{ formatAction(perm.name) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </ComponentCard>
            <SubmitButton :processing="form.processing" label="CẬP NHẬT THAY ĐỔI" />
        </form>
    </AdminLayout>
</template>