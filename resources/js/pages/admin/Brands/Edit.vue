<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import { route } from 'ziggy-js'; 
import InputLabel from '@/components/ui/label/InputLabel.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
const props = defineProps({
    brand:Object,
});

const form = useForm({
    _method: 'PUT',
    brand_name: props.brand.brand_name,
});

const submit = () => {
    form.post(route('admin.brands.update', props.brand.id), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="'Sửa: ' + brand.brand_name" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Sửa thương hiệu" parentName="Thương hiệu" :parentRoute="route('admin.brands.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thông tin cơ bản">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Thương hiệu <span class="text-red-500">*</span></InputLabel>
                            <Input v-model="form.brand_name" :error="form.errors.brand_name" />
                        </div>
                    </div>
                </ComponentCard>
                <SubmitButton :processing="form.processing" label="CẬP NHẬT THƯƠNG HIỆU" loadingLabel="ĐANG LƯU..." />
            </div>
        </form>
    </AdminLayout>
</template>