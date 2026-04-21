<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import {computed, watch} from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputTextArea from '@/components/admin/forms/FormElements/InputTextArea.vue';
import FileMultipleInput from '@/components/admin/forms/FormElements/FileMultipleInput.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import FileInput from '@/components/admin/forms/FormElements/FileInput.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
const props = defineProps({
    genders: Array,
    productTypes: Array,
    categories: Array,
    brands: Array,
    attributes: Array,
})

const form = useForm({
    product_name: '',
    gender: null, 
    product_type_id: null,
    parent_category_id: null,
    category_id: null,
    brand_id: null,
    description: '',
    thumbnail: null,
    images: [],
    is_active: true,
    is_featured: false,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    variants: [{ sku: '', price: 0, stock: 0, low_stock_threshold: 10, attribute_values: {} ,image: null}],
});

const parentCategoryOptions = computed(() => {
    if(!form.gender || !form.product_type_id) return [];
    return props.categories.filter(c => 
        c.parent_id === null &&
        c.gender === form.gender &&
        c.product_type_id === form.product_type_id
    );
});

const subCategoryOptions = computed(() => {
    if (!form.parent_category_id) return [];
    return props.categories.filter(c => c.parent_id === form.parent_category_id);
});
watch(() => form.gender, () => { 
    form.parent_category_id = null; 
});

watch(() => form.product_type_id, () => { 
    form.parent_category_id = null; 
});

watch(() => form.parent_category_id, () => {
    form.category_id = null;
});

const addVariant = () => {
    form.variants.push({sku: '', price: 0, stock: 0, attribute_values: {} , image: null});
}

const removeVariant = (index) => {
    if(form.variants?.length > 1){
        form.variants.splice(index, 1);
    }
}

const slugifySKU = (str) => {
    if (!str) return '';
    return str
        .toString()
        .normalize('NFD')                   
        .replace(/[\u0300-\u036f]/g, '')     
        .replace(/[đĐ]/g, 'd')               
        .replace(/([^0-9a-z-\s])/gi, '')    
        .replace(/\s+/g, '-')               
        .toUpperCase()                       
        .trim();
};

watch(
    () => [form.product_name, form.variants], 
    ([newName, newVariants]) => {
        newVariants.forEach((variant, index) => {
            let skuParts = [];
            if (newName) {
                skuParts.push(slugifySKU(newName));
            }
            props.attributes.forEach(attr => {
                const selectedValueId = variant.attribute_values[attr.id];
                if (selectedValueId) {
                    const foundValue = attr.values.find(val => val.id === selectedValueId);
                    if (foundValue) {
                        skuParts.push(slugifySKU(foundValue.value));
                    }
                }
            });
            const generatedSKU = skuParts.join('-');
            if (variant.sku !== generatedSKU) {
                variant.sku = generatedSKU;
            }
        });
    }, 
    { deep: true } 
);

const submit = () => {
    form.post(route('admin.products.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>
<template>
    <AdminLayout title="Thêm sản phẩm">
        <Head title="Thêm sản phẩm" />
        <PageBreadcrumb pageTitle="Thêm sản phẩm" parentName="Sản phẩm" :parentRoute="route('admin.products.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-6">
                    <ComponentCard title="Thông tin cơ bản">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Tên sản phẩm <span class="text-red-500">*</span></InputLabel>
                                <Input v-model="form.product_name" placeholder="Nhập tên sản phẩm" :error="form.errors.product_name" />
                            </div>
                            <div>
                                <InputLabel>Mô tả</InputLabel>
                                <InputTextArea
                                    v-model="form.description"
                                    placeholder="Nhập mô tả"
                                    :error="form.errors.description"
                                />
                            </div>
                        </div>
                    </ComponentCard>
                    <ComponentCard title="Biến thể & Thuộc tính">
                        <div v-for="(v, index) in form.variants" :key="index" class="p-4 border rounded-xl mb-4 bg-gray-50/50">
                            <div class="grid grid-cols-4 gap-3 mb-4">
                                <div>
                                    <InputLabel>Mã định danh (sku)<span class="text-red-500">*</span></InputLabel>
                                    <Input v-model="v.sku" placeholder="SKU" :error="form.errors[`variants.${index}.sku`]" />
                                </div>
                                
                                <div>
                                    <InputLabel>Giá bán <span class="text-red-500">*</span></InputLabel>
                                    <NumberInput v-model="v.price"/>
                                </div>
                                <div>
                                    <InputLabel>Số lượng <span class="text-red-500">*</span></InputLabel>
                                    <NumberInput v-model="v.stock" />
                                </div>
                                <div>
                                    <InputLabel>Cảnh báo tồn kho <span class="text-red-500">*</span></InputLabel>
                                    <NumberInput v-model="v.low_stock_threshold" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div v-for="attr in attributes" :key="attr.id">
                                    <InputLabel>
                                        {{ attr.attribute_name }}
                                    </InputLabel>
                                    
                                    <SingleSelect 
                                        v-model="v.attribute_values[attr.id]" 
                                        :options="attr.values" 
                                        option-label="value" 
                                        option-value="id"
                                        :placeholder="'Chọn ' + attr.attribute_name"
                                        :error="form.errors[`variants.${index}.attribute_values.${attr.id}`]"
                                    >
                                        <template #label="{ selected }">
                                            <div v-if="selected" class="flex items-center gap-2">
                                                <span 
                                                    v-if="selected.hex_code" 
                                                    class="w-3.5 h-3.5 rounded-full border border-gray-300 shadow-sm" 
                                                    :style="{ backgroundColor: selected.hex_code }"
                                                ></span>
                                                <span class="font-medium text-gray-700">{{ selected.value }}</span>
                                            </div>
                                        </template>

                                        <template #option="{ option }">
                                            <div class="flex items-center gap-2">
                                                <span 
                                                    v-if="option.hex_code" 
                                                    class="w-4 h-4 rounded-full border border-gray-200 shadow-inner" 
                                                    :style="{ backgroundColor: option.hex_code }"
                                                ></span>
                                                <span :class="{ 'font-medium': v.attribute_values[attr.id] === option.id }">
                                                    {{ option.value }}
                                                </span>
                                            </div>
                                        </template>
                                    </SingleSelect>
                                </div>
                                <div>
                                    <FileInput 
                                        label="Ảnh cho biến thể"
                                        :model-value="v.image"
                                        @change="(e) => v.image = e.target.files[0]" 
                                        :error="form.errors[`variants.${index}.image`]"
                                    />
                                    <p class="mt-3 text-[14px] text-gray-600 italic">* Nếu không chọn, sẽ dùng ảnh đại diện sản phẩm.</p>
                                </div>
                            </div>
                            <button v-if="form.variants.length > 1" @click="removeVariant(index)" type="button" class="mt-2 text-xs text-red-500 font-bold">XÓA DÒNG</button>
                        </div>
                        <button type="button" @click="addVariant" class="text-sm font-bold text-blue-600">
                            + THÊM BIẾN THỂ
                        </button>
                    </ComponentCard>
                </div>

                <div class="space-y-6">
                    <ComponentCard title="Phân loại chi tiết">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Thương hiệu <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect v-model="form.brand_id" :options="brands" option-label="brand_name" option-value="id" :error="form.errors.brand_id" />
                            </div>
                            
                            <div>
                                <InputLabel>Giới tính <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect 
                                    v-model="form.gender" 
                                    :options="genders" 
                                    option-label="label" 
                                    option-value="value" 
                                    :error="form.errors.gender" 
                                />
                            </div>
                            <div v-if="form.gender">
                                <InputLabel>Loại sản phẩm <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect v-model="form.product_type_id" :options="productTypes" option-label="type_name" option-value="id" />
                            </div>
                            <div v-if="form.product_type_id">
                                <InputLabel>Danh mục cha <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect v-model="form.parent_category_id" :options="parentCategoryOptions" option-label="category_name" option-value="id" :error="form.errors.category_id" />
                            </div>
                            <div v-if="form.parent_category_id">
                                <InputLabel>Danh mục con <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect v-model="form.category_id" :options="subCategoryOptions" option-label="category_name" option-value="id" :error="form.errors.category_id" />
                            </div>
                        </div>
                    </ComponentCard>
                    <ComponentCard title="Cấu hình SEO">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Meta Title</InputLabel>
                                <Input 
                                    v-model="form.meta_title" 
                                    placeholder="Nếu trống sẽ lấy tên sản phẩm" 
                                    :error="form.errors.meta_title" 
                                />
                                <p class="mt-1 text-xs text-gray-500 italic">50-60 ký tự.</p>
                            </div>
                            <div>
                                <InputLabel>Meta Description</InputLabel>
                                <InputTextArea
                                    v-model="form.meta_description"
                                    placeholder="Mô tả ngắn gọn về sản phẩm để hiển thị trên Google"
                                    :error="form.errors.meta_description"
                                />
                            </div>
                            <div>
                                <InputLabel>Meta Keywords</InputLabel>
                                <Input 
                                    v-model="form.meta_keywords" 
                                    placeholder="Ví dụ: áo thun nam, thời trang hè, cotton" 
                                    :error="form.errors.meta_keywords" 
                                />
                            </div>
                        </div>
                    </ComponentCard>
                    <ComponentCard title="Hình ảnh">
                        <div class="space-y-4">
                                <InputLabel>Ảnh đại diện <span class="text-red-500">*</span></InputLabel>
                                <FileInput v-model="form.thumbnail" :error="form.errors.thumbnail" />

                                <InputLabel>Bộ sưu tập ảnh</InputLabel>
                                <FileMultipleInput v-model="form.images" />
                        </div>
                    </ComponentCard>
                    <ComponentCard title="Cài đặt hiển thị & Phổ biến">
                        <ToggleSwitch v-model="form.is_active" label="Trạng thái hoạt động" description="Bật để hiển thị sản phẩm lên website."/>
                        <ToggleSwitch v-model="form.is_featured" label="Trạng thái nổi bật" description="Bật để hiển thị sản phẩm lên mục phổ biến."/>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM SẢN PHẨM" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>