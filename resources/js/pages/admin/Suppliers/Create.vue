<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const form = useForm({
    supplier_name: '',
    phone: '',
    supplier_address: '',
});

const submit = () => {
    form.post(route('admin.suppliers.store'), {
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Thêm nhà cung cấp">
        <Head title="Thêm nhà cung cấp" />
        <PageBreadcrumb pageTitle="Thêm nhà cung cấp" parentName="Nhà cung cấp" :parentRoute="route('admin.suppliers.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
                <div class="lg:col-span-8  space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Tên nhà cung cấp (*)</InputLabel>
                                <Input v-model="form.supplier_name" placeholder="Nhập tên nhà cung cấp" :error="form.errors.supplier_name" />
                            </div>
                            <div>
                                <InputLabel>Số điện thoại (*)</InputLabel>
                                <Input v-model="form.phone" placeholder="Nhập số điện thoại" :error="form.errors.phone" />
                            </div>
                            <div>
                                <InputLabel>Địa chỉ (*)</InputLabel>
                                <Input v-model="form.supplier_address" placeholder="Nhập địa chỉ" :error="form.errors.supplier_address" />
                            </div>
                        </div>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM NHÀ CUNG CẤP" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>