<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import {computed} from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const props = defineProps({
    roles: Array,
});

const form = useForm({
    full_name: '',
    email: '',
    password: '',
    roles: [],
});

const roleOptions = computed(() => 
    props.roles?.map(role => ({
        label: role.name,
        value: role.id
    })) || []
);

const submit = () => {
    form.post(route('admin.users.store'), {
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Thêm người dùng">
        <Head title="Thêm người dùng" />
        <PageBreadcrumb pageTitle="Thêm người dùng" parentName="Người dùng" :parentRoute="route('admin.users.index')" />
        
        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-8 space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div>
                                <InputLabel>Họ và tên <span class="text-red-500">*</span></InputLabel>
                                <Input 
                                    v-model="form.full_name" 
                                    placeholder="Nhập tên người dùng" 
                                    :error="form.errors.name" 
                                />
                            </div>
                            <div>
                                <InputLabel>Địa chỉ Email <span class="text-red-500">*</span></InputLabel>
                                <Input 
                                    v-model="form.email" 
                                    type="email"
                                    placeholder="example@gmail.com" 
                                    :error="form.errors.email" 
                                />
                            </div>
                            <div>
                                <InputLabel>Mật khẩu <span class="text-red-500">*</span></InputLabel>
                                <Input 
                                    v-model="form.password" 
                                    type="password"
                                    placeholder="Nhập mật khẩu (tối thiểu 8 ký tự)" 
                                    :error="form.errors.password" 
                                />
                            </div>
                    </ComponentCard>

                </div>
                <div class="lg:col-span-4 space-y-6">
                    <ComponentCard title="Phân quyền">
                        <div>
                            <InputLabel>Vai trò</InputLabel>
                            <div class="grid grid-cols-1 gap-2 mt-2">
                                <label v-for="role in roleOptions" :key="role.value" class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input 
                                        type="checkbox" 
                                        :value="role.value" 
                                        v-model="form.roles"
                                        class="w-4 h-4 text-brand-500 border-gray-300 rounded focus:ring-brand-500"
                                    >
                                    <span class="text-sm font-medium text-gray-700">{{ role.label }}</span>
                                    
                                </label>
                            </div>
                            <p class="mt-3 text-[14px] text-gray-600 italic">
                                * Lưu ý: Mặc định là <strong class="text-brand-500">customer</strong> nếu không chọn!
                            </p>
                        </div>
                    </ComponentCard>
                    
                </div>
            </div>
            <SubmitButton :processing="form.processing" label="THÊM NGƯỜI DÙNG" loadingLabel="ĐANG LƯU..." />
        </form>
    </AdminLayout>
</template>