<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import { route } from 'ziggy-js'; 
import InputLabel from '@/components/ui/label/InputLabel.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
// 
const props = defineProps({
    supplier: Object,
});
// Form
const form = useForm({
    _method: 'PUT',
    supplier_name: props.supplier.supplier_name,
    phone: props.supplier.phone,
    supplier_address: props.supplier.supplier_address,
});

const submit = () => {
    form.post(route('admin.suppliers.update', props.supplier.id), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="'Sửa: ' + supplier.supplier_name" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Chỉnh sửa nhà cung cấp" parentName="Nhà cung cấp" :parentRoute="route('admin.suppliers.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thông tin cơ bản">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Tên nhà cung cấp (*)</InputLabel>
                            <Input v-model="form.supplier_name" placeholder="Nhập tên nhóm" :error="form.errors.supplier_name" />
                        </div>
                        <div>
                            <InputLabel>Số điện thoại (*)</InputLabel>
                            <Input v-model="form.phone" placeholder="Nhập mã nhóm" :error="form.errors.phone" />
                        </div>
                        <div>
                            <InputLabel>Địa chỉ (*)</InputLabel>
                            <Input v-model="form.supplier_address" placeholder="Nhập mô tả" :error="form.errors.supplier_address" />
                        </div>
                    </div>
                </ComponentCard>
                <SubmitButton :processing="form.processing" label="LƯU NHÀ CUNG CẤP" loadingLabel="ĐANG LƯU..." />
            </div>
        </form>
    </AdminLayout>
</template>