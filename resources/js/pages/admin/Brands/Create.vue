<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const form = useForm({
    brand_name: '',
});

const submit = () => {
    form.post(route('admin.brands.store'), {
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Thêm thương hiệu">
        <Head title="Thêm thương hiệu" />
        <PageBreadcrumb pageTitle="Thêm thương hiệu" parentName="Thương hiệu" :parentRoute="route('admin.brands.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
                <div class="lg:col-span-8  space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Thương hiệu</InputLabel>
                                <Input v-model="form.brand_name" placeholder="Nhập thương hiệu" :error="form.errors.brand_name" />
                            </div>
                        </div>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM THƯƠNG HIỆU" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>