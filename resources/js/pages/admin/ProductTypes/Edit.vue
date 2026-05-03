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
    productType:Object,
});

// Form
const form = useForm({
    _method: 'PUT',
    type_name: props.productType.type_name,
});

const submit = () => {
    form.post(route('admin.producttypes.update', props.productType.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="'Sửa: ' + productType.type_name" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Sửa loại sản phẩm" parentName="Loại sản phẩm" :parentRoute="route('admin.producttypes.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
            
            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thông tin cơ bản">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <InputLabel>Loại sản phẩm <span class="text-red-500">*</span></InputLabel>
                            <Input v-model="form.type_name" :error="form.errors.type_name" />
                        </div>
                    </div>
                </ComponentCard>
                <SubmitButton :processing="form.processing" label="CẬP NHẬT LOẠI SẢN PHẨM" loadingLabel="ĐANG LƯU..." />
            </div>
        </form>
    </AdminLayout>
</template>