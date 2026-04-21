<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import InputTextArea from '@/components/admin/forms/FormElements/InputTextArea.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import FileInput from '@/components/admin/forms/FormElements/FileInput.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import FileMultipleInput from '@/components/admin/forms/FormElements/FileMultipleInput.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    genders: Array,
    productTypes: Array,
    attributes: Array,
});

const form = useForm({
    _method: 'PUT',
    product_name: props.product.product_name,
    description: props.product.product_description,
    brand_id: props.product.brand_id,
    gender: props.product.category?.gender,
    product_type_id: props.product.category?.product_type_id,
    parent_category_id: props.product.category?.parent_id,
    category_id: props.product.category_id,
    is_active: props.product.is_active,
    is_featured: props.product.is_featured,
    meta_title: props.product.meta_title,
    meta_description: props.product.meta_description,
    meta_keywords: props.product.meta_keywords,
    thumbnail: null,
    images: [],
    deleted_image_ids: [],
    
    //Map dữ liệu variants
    variants: props.product.variants.map(v => {
        // Chuyển mảng attribute_values thành object {attr_id: val_id}
        const attrMap = {};
        v.attribute_values.forEach(av => {
            attrMap[av.attribute_id] = av.id;
        });

        return {
            id: v.id,
            sku: v.sku,
            price: v.price,
            stock: v.stock_quantity,
            low_stock_threshold: v.low_stock_threshold,
            attribute_values: attrMap,
            image: null,
            current_image: props.product.images.find(img => img.variant_id === v.id)?.image_path
        };
    })
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

const currentGallery = ref(props.product.images.filter(img => !img.is_primary && !img.variant_id));
const primaryImage = computed(() => props.product.images.find(img => img.is_primary)?.image_path);

const removeExistingImage = (id) => {
    form.deleted_image_ids.push(id);
    currentGallery.value = currentGallery.value.filter(img => img.id !== id);
};

const addVariant = () => {
    form.variants.push({
        id: null,
        sku: '',
        price: 0,
        stock: 0,
        attribute_values: {},
        image: null,
        current_image: null
    });
};

const removeVariant = (index) => {
    if (form.variants.length > 1) {
        const variant = form.variants[index];
        if (variant.id) {
            if (confirm('Biến thể này đã tồn tại trên hệ thống. Bạn có chắc chắn muốn xóa?')) {
                form.variants.splice(index, 1);
            }
        } else {
            form.variants.splice(index, 1);
        }
    } else {
        alert('Sản phẩm phải có ít nhất một biến thể.');
    }
};

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
//Tạo SKU cho 1 biến thể cụ thể
const generateSKUForVariant = (variant) => {
    let skuParts = [];
    if (form.product_name) skuParts.push(slugifySKU(form.product_name));

    props.attributes.forEach(attr => {
        const valId = variant.attribute_values[attr.id];
        if (valId) {
            const found = attr.values.find(v => v.id === valId);
            if (found) skuParts.push(slugifySKU(found.value));
        }
    });
    return skuParts.join('-');
};

watch(
    () => [form.product_name, form.variants],
    ([newName, newVariants]) => {
        newVariants.forEach((v) => {
            //Tự động tạo SKU cho biến thể mới
            if (!v.id) {
                const newSKU = generateSKUForVariant(v);
                if (v.sku !== newSKU) v.sku = newSKU;
            }
        });
    },
    { deep: true }
);

const forceGenerateSKU = (index) => {
    form.variants[index].sku = generateSKUForVariant(form.variants[index]);
};

const submit = () => {
    form.post(route('admin.products.update', props.product.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Chỉnh sửa sản phẩm">
        <Head title="Sửa sản phẩm" />
        <PageBreadcrumb pageTitle="Sửa sản phẩm" parentName="Sản phẩm" :parentRoute="route('admin.products.index')" />
        <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-20">
            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thông tin chung">
                    <InputLabel>Tên sản phẩm</InputLabel>
                    <Input v-model="form.product_name" />
                    <InputLabel class="mt-4">Mô tả chi tiết</InputLabel>
                    <InputTextArea v-model="form.description" />
                </ComponentCard>

                <ComponentCard title="Biến thể & Thuộc tính">
                    <div v-for="(v, index) in form.variants" :key="index" class="p-4 border rounded-xl mb-4 bg-gray-50/50">
                        
                        <div class="grid grid-cols-4 gap-3 mb-4">
                            <div>
                                <div class="flex justify-between items-center">
                                    <InputLabel>SKU <span class="text-red-500">*</span></InputLabel>
                                    <button 
                                        type="button" 
                                        @click="forceGenerateSKU(index)"
                                        class="text-[10px] text-blue-500 hover:underline"
                                        title="Tạo lại mã theo Tên - Màu - Size"
                                    >
                                        Gợi ý mã mới
                                    </button>
                                </div>
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
                                <InputLabel>{{ attr.attribute_name }}</InputLabel>
                                <SingleSelect 
                                    v-model="v.attribute_values[attr.id]" 
                                    :options="attr.values" 
                                    option-label="value" 
                                    option-value="id"
                                    :placeholder="'Chọn ' + attr.attribute_name"
                                >
                                    <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <span v-if="option.hex_code" class="w-3 h-3 rounded-full border" :style="{backgroundColor: option.hex_code}"></span>
                                            <span>{{ option.value }}</span>
                                        </div>
                                    </template>
                                </SingleSelect>
                            </div>
                            <div>
                                <div v-if="v.current_image" class="w-12 h-12 rounded border overflow-hidden shrink-0">
                                    <img :src="'/storage/' + v.current_image" class="w-full h-full object-cover">
                                </div>
                                <FileInput v-model="v.image" label="Đổi ảnh cho biến thể" />
                            </div>
                        </div>
                        <button v-if="form.variants.length > 1" @click="removeVariant(index)" type="button" class="mt-2 text-xs text-red-500 font-bold">XÓA DÒNG</button>
                    </div>
                    <button type="button" @click="addVariant" class="text-sm font-bold text-blue-600">
                            + THÊM BIẾN THỂ
                        </button>
                </ComponentCard>
                
            </div>

            <div class="lg:col-span-4 space-y-6">
                <ComponentCard title="Phân loại">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Thương hiệu <span class="text-red-500">*</span></InputLabel>
                            <SingleSelect v-model="form.brand_id" :options="brands" option-label="brand_name" option-value="id" :error="form.errors.brand_id" />
                        </div>
                        
                        <div>
                            <InputLabel>Giới tính <span class="text-red-500">*</span></InputLabel>
                            <SingleSelect v-model="form.gender" :options="genders" option-label="label" option-value="value" :error="form.errors.gender_id" />
                        </div>
                        <div v-if="form.gender_id">
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
                <ComponentCard title="Hình ảnh hiện tại">
                    <div class="space-y-4">
                        <div v-if="primaryImage">
                            <InputLabel>Ảnh đại diện cũ</InputLabel>
                            <img :src="'/storage/' + primaryImage" class="w-32 h-32 rounded-lg object-cover border">
                        </div>
                        <FileInput v-model="form.thumbnail" label="Tải ảnh đại diện mới" />
                        
                        <div class="border-t pt-4">
                            <InputLabel>Gallery hiện tại</InputLabel>
                            <div class="grid grid-cols-3 gap-2">
                                <div v-for="img in currentGallery" :key="img.id" class="relative group">
                                    <img :src="'/storage/' + img.image_path" class="w-full aspect-square object-cover rounded">
                                    <button @click="removeExistingImage(img.id)" type="button" class="absolute top-0 right-0 bg-red-500 text-white w-6 h-6 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                                </div>
                            </div>
                        </div>
                        <FileMultipleInput v-model="form.images" label="Thêm ảnh vào Gallery" />
                    </div>
                </ComponentCard>
                <ComponentCard title="Cài đặt hiển thị">
                    <ToggleSwitch v-model="form.is_active" label="Trạng thái hoạt động" description="Bật để hiển thị sản phẩm lên website."/>
                    <ToggleSwitch v-model="form.is_featured" label="Trạng thái nổi bật" description="Bật để hiển thị sản phẩm lên mục phổ biến."/>
                </ComponentCard>
                <SubmitButton :processing="form.processing" label="CẬP NHẬT SẢN PHẨM" />
            </div>
        </form>
    </AdminLayout>
</template>