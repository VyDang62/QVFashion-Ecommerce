<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import { route } from 'ziggy-js'; 
import InputLabel from '@/components/ui/label/InputLabel.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
// 
const props = defineProps({
    category:Object,
    genders:Object,
    productTypes:Object,
    parentCategories:Object,
    hasChildren:Boolean,
});

// Form
const form = useForm({
    _method: 'PUT',
    category_name: props.category.category_name,
    parent_id: props.category.parent_id,
    gender: props.category.gender,
    product_type_id: props.category.product_type.id,
});

const submit = () => {
    form.post(route('admin.categories.update', props.category.id), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="'Sửa: ' + category.category_name" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Sửa danh mục" parentName="Danh mục" :parentRoute="route('admin.categories.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
            <div class="lg:col-span-8 space-y-6">
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
                            <SingleSelect v-model="form.parent_id" :disabled="hasChildren" :options="parentCategories" option-label="category_name" option-value="id" placeholder="Chọn hoặc để trống" />
                            <p v-if="hasChildren" class="text-[11px] text-amber-600 mt-1">
                                * Danh mục này đang là danh mục cha, không thể chuyển nó thành danh mục con.
                            </p>
                        </div>
                        <div>
                            <InputLabel>Tên danh mục</InputLabel>
                            <Input v-model="form.category_name" placeholder="Nhập danh mục" :error="form.errors.category_name" />
                        </div>
                    </div>
                </ComponentCard>
                <SubmitButton :processing="form.processing" label="CẬP NHẬT DANH MỤC" loadingLabel="ĐANG LƯU..." />
            </div>
        </form>
    </AdminLayout>
</template>