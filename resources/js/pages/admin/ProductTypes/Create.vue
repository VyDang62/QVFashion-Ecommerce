<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const form = useForm({
    type_name: '',
});

const submit = () => {
    form.post(route('admin.producttypes.store'), {
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Thêm loại sản phẩm">
        <Head title="Thêm loại sản phẩm" />
        <PageBreadcrumb pageTitle="Thêm loại sản phẩm" parentName="Loại sản phẩm" :parentRoute="route('admin.producttypes.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
                <div class="lg:col-span-8  space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Loại sản phẩm <span class="text-red-500">*</span></InputLabel>
                                <Input v-model="form.type_name" placeholder="Nhập loại sản phẩm" :error="form.errors.type_name" />
                            </div>
                        </div>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM LOẠI SẢN PHẨM" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>