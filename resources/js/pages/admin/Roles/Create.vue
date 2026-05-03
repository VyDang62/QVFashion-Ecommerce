<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import Input from '@/components/ui/input/Input.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const props = defineProps({
    permissionsGrouped: Object, 
});

const form = useForm({
    name: '',
    permissions: [], 
});


const formatAction = (name) => {
    const action = name.split('.')[1];
    const labels = {
        'view': 'Xem',
        'create': 'Thêm mới',
        'edit': 'Chỉnh sửa',
        'delete': 'Xóa',
        'cancel': 'Hủy bỏ',
        'approve': 'Duyệt',
    };
    return labels[action] || action;
};


const toggleGroup = (modulePermissions, isChecked) => {
    modulePermissions.forEach(perm => {
        const index = form.permissions.indexOf(perm.name);
        if (isChecked && index === -1) {
            form.permissions.push(perm.name);
        } else if (!isChecked && index !== -1) {
            form.permissions.splice(index, 1);
        }
    });
};



const submit = () => {
    form.post(route('admin.roles.store'));
};
</script>

<template>
    <Head title="Tạo vai trò mới" />

    <AdminLayout>
        <PageBreadcrumb pageTitle="Tạo vai trò mới" parentName="Vai trò" :parentRoute="route('admin.roles.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-1 space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div class="space-y-4">
                            <div>
                                <InputLabel for="name">Tên vai trò <span class="text-red-500">*</span></InputLabel>
                                <Input 
                                    id="name" 
                                    v-model="form.name" 
                                    placeholder="Ví dụ: warehouse-manager" 
                                    :error="form.errors.name"
                                />
                            </div>
                        </div>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM VAI TRÒ" class="w-full" />
                </div>

                <div class="lg:col-span-2">
                    <ComponentCard title="Phân quyền chi tiết">
                        <div class="space-y-8">
                            <div v-for="(perms, moduleName) in permissionsGrouped" :key="moduleName" class="p-4 border border-gray-100 rounded-xl">
                                <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-50">
                                    <h3 class="font-bold text-blue-600 uppercase text-sm tracking-wider">
                                        QUẢN LÝ {{ moduleName.replace('-', ' ') }}
                                    </h3>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            @change="toggleGroup(perms, $event.target.checked)"
                                        >
                                        <span class="text-xs font-medium text-gray-500 uppercase">Chọn tất cả</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div v-for="perm in perms" :key="perm.id" class="flex items-center gap-3 group">
                                        <input 
                                            type="checkbox" 
                                            :id="'perm-' + perm.id"
                                            :value="perm.name" 
                                            v-model="form.permissions"
                                            class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                        />
                                        <label :for="'perm-' + perm.id" class="text-sm text-gray-700 cursor-pointer group-hover:text-blue-600 transition-colors">
                                            {{ formatAction(perm.name) }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ComponentCard>
                </div>

            </div>
        </form>
    </AdminLayout>
</template>