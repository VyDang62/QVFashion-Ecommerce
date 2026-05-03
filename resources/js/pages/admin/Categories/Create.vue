<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
const props = defineProps({
    genders:Array,
    productTypes: Array,
    parentCategories:Array,
})

const form = useForm({
    category_name: '',
    parent_id: null,
    gender: null,
    product_type_id: null,
});

const submit = () => {
    form.post(route('admin.categories.store'), {
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Thêm danh mục">
        <Head title="Thêm danh mục" />
        <PageBreadcrumb pageTitle="Thêm danh mục" parentName="Danh mục" :parentRoute="route('admin.categories.index')" />
        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
                <div class="lg:col-span-8  space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Giới tính <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect 
                                    v-model="form.gender" 
                                    :options="genders" 
                                    option-label="label" 
                                    option-value="value" 
                                    placeholder="Chọn giới tính..." 
                                    :error="form.errors.gender"
                                />
                            </div>
                            <div>
                                <InputLabel>Loại sản phẩm <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect v-model="form.product_type_id" :options="productTypes" option-label="type_name" option-value="id" placeholder="Chọn loại sản phẩm..." :error="form.errors.product_type_id"/>
                            </div>
                            <div>
                                <InputLabel>Danh mục cha</InputLabel>
                                <SingleSelect v-model="form.parent_id" :options="parentCategories" option-label="category_name" option-value="id" placeholder="Chọn hoặc để trống" />
                            </div>
                            <div>
                                <InputLabel>Danh mục</InputLabel>
                                <Input v-model="form.category_name" placeholder="Nhập danh mục" :error="form.errors.category_name" />
                            </div>
                        </div>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM DANH MỤC" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>