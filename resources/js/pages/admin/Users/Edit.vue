<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    userRoles: Array,
});

//Form 1: Thông tin cơ bản
const infoForm = useForm({
    full_name: props.user.full_name,
    email: props.user.email,
    roles: props.userRoles || [],
});

//Form 2: Mật khẩu
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const roleOptions = computed(() => 
    props.roles?.map(role => ({ label: role.name, value: role.id })) || []
);

const submitInfo = () => {
    infoForm.patch(route('admin.users.updateinfo', props.user.id), {
        preserveScroll: true,
    });
};

const submitPassword = () => {
    passwordForm.patch(route('admin.users.updatepassword', props.user.id), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <AdminLayout title="Sửa người dùng">
        <Head title="Sửa người dùng" />
        <PageBreadcrumb pageTitle="Sửa người dùng" parentName="Người dùng" :parentRoute="route('admin.users.index')" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            
            <div class="lg:col-span-8 space-y-6">
                <form @submit.prevent="submitInfo">
                    <ComponentCard title="Thông tin cơ bản & Phân quyền">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Họ và tên <span class="text-red-500">*</span></InputLabel>
                                <Input v-model="infoForm.full_name" :error="infoForm.errors.full_name" />
                            </div>
                            <div>
                                <InputLabel>Địa chỉ Email <span class="text-red-500">*</span></InputLabel>
                                <Input v-model="infoForm.email" type="email" :error="infoForm.errors.email" />
                            </div>
                            <div class="pt-4 border-t border-gray-100">
                                <InputLabel>Vai trò hệ thống</InputLabel>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                    <label v-for="role in roleOptions" :key="role.value" class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                        <input type="checkbox" :value="role.label" v-model="infoForm.roles" class="w-4 h-4 text-brand-500 border-gray-300 rounded focus:ring-brand-500">
                                        <span class="text-sm font-medium text-gray-700">{{ role.label }}</span>
                                    </label>
                                </div>
                                <p class="mt-3 text-[14px] text-gray-600 italic">* Mặc định là <strong class="text-brand-500">customer</strong> nếu không chọn!</p>
                            </div>
                            <div class="flex justify-end pt-4">
                                <SubmitButton :processing="infoForm.processing" label="CẬP NHẬT THÔNG TIN" loadingLabel="ĐANG LƯU..." />
                            </div>
                        </div>
                    </ComponentCard>
                </form>
            </div>

            <div class="lg:col-span-4 space-y-6">
                <form @submit.prevent="submitPassword">
                    <ComponentCard title="Đổi mật khẩu">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Mật khẩu mới</InputLabel>
                                <Input v-model="passwordForm.password" type="password" placeholder="Nhập mật khẩu mới" :error="passwordForm.errors.password" />
                            </div>
                            <div>
                                <InputLabel>Xác nhận mật khẩu</InputLabel>
                                <Input v-model="passwordForm.password_confirmation" type="password" placeholder="Nhập lại mật khẩu" />
                            </div>
                            <div class="pt-2">
                                <SubmitButton :processing="passwordForm.processing" class="w-full" label="ĐỔI MẬT KHẨU" loadingLabel="ĐANG LƯU..." />
                            </div>
                        </div>
                    </ComponentCard>
                </form>
            </div>

        </div>
    </AdminLayout>
</template>