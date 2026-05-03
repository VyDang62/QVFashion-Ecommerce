<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
import { computed } from 'vue';
import { useFormatter } from '@/composables/useFormatter';
const props = defineProps({
    flashSale: Object,
    products: Array,
})

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
};

const form = useForm({
    _method: 'PUT',
    name: props.flashSale.name,
    start_date: formatDateTime(props.flashSale.start_date),
    end_date: formatDateTime(props.flashSale.end_date),
    is_active: !!props.flashSale.is_active,
    items: props.flashSale.items.map(item => ({
        id: item.id,
        product_id: item.variant.product_id,
        product_variant_id: item.product_variant_id,
        sale_price: item.sale_price,
        sale_quantity: item.sale_quantity,
        user_limit: item.user_limit,
    })),
});

const getVariants = (productId) => {
    const product = props.products.find(p => p.id === productId);
    return product ? product.variants : [];
};

const addItem = () => {
    form.items.push({ product_id: null, product_variant_id: null, sale_price: 0, sale_quantity: 0, user_limit: 1 });
};

const removeItem = (index) => {
    if (form.items.length > 1) form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('admin.flashsales.update', props.flashSale.id), {
        preserveScroll: true,
    });
};

const itemsData = computed(() => {
    return form.items.map((item) => {
        const product = props.products.find(p => p.id === item.product_id);
        const variant = product?.variants?.find(v => v.id === item.product_variant_id);
        
        const originalPrice = variant ? variant.price : 0;
        
        return {
            originalPrice: originalPrice,
            isInvalid: item.product_variant_id && item.sale_price >= originalPrice,
            discountPercent: originalPrice > 0 
                ? Math.round(((originalPrice - item.sale_price) / originalPrice) * 100) 
                : 0
        };
    });
});
const {formatPrice} = useFormatter();
</script>
<template>
    <AdminLayout title="Sửa FlashSale">
        <Head :title="`Sửa Voucher: ${flashSale.name}`" />
        <PageBreadcrumb :pageTitle="`Chỉnh sửa: ${flashSale.name}`" parentName="Flash Sale" :parentRoute="route('admin.flashsales.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1 space-y-6">
            <ComponentCard title="Thông tin">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Tên chương trình <span class="text-red-500">*</span></InputLabel>
                            <Input v-model="form.name" placeholder="VÍ DỤ: Flash Sale 12.12" :error="form.errors.name" />
                        </div>
                        
                        <div>
                            <InputLabel>Thời gian bắt đầu <span class="text-red-500">*</span></InputLabel>
                            <input 
                                type="datetime-local" 
                                v-model="form.start_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-primary focus:border-primary"
                            />
                            <p v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</p>
                        </div>

                        <div>
                            <InputLabel>Thời gian kết thúc <span class="text-red-500">*</span></InputLabel>
                            <input 
                                type="datetime-local" 
                                v-model="form.end_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-primary focus:border-primary"
                            />
                            <p v-if="form.errors.end_date" class="text-red-500 text-xs mt-1">{{ form.errors.end_date }}</p>
                        </div>

                        <ToggleSwitch 
                            v-model="form.is_active" 
                            label="Kích hoạt" 
                            description="Bật để Flash Sale chạy khi đến giờ."
                        />
                    </div>
                </ComponentCard>
                <SubmitButton :processing="form.processing" label="LƯU FLASH SALE" loadingLabel="ĐANG LƯU..." />
        </div>
        <div class="lg:col-span-2 space-y-6">
                <ComponentCard title="Danh sách sản phẩm">
                    <div v-for="(item, index) in form.items" :key="index" class="p-4 border rounded-xl mb-4 bg-gray-50/50 relative">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel>Sản phẩm <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect 
                                    v-model="item.product_id" 
                                    :options="products" 
                                    option-label="product_name" 
                                    option-value="id"
                                    placeholder="Tìm sản phẩm..."
                                    @change="item.product_variant_id = null"
                                />
                            </div>

                            <div>
                                <InputLabel>Biến thể (SKU) <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect 
                                    v-model="item.product_variant_id" 
                                    :options="getVariants(item.product_id)" 
                                    option-label="sku" 
                                    option-value="id"
                                    :disabled="!item.product_id"
                                    placeholder="Chọn SKU"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel>
                                    Giá Sale <span class="text-red-500">*</span>
                                    <span v-if="item.product_variant_id">
                                        (Gốc: {{ formatPrice(itemsData[index].originalPrice) }})
                                    </span>
                                </InputLabel>
                                <NumberInput v-model="item.sale_price" unit="VND"/>
                                <p v-if="item.product_variant_id && item.sale_price > 0" class="text-sm text-green-700 mt-1">
                                    Giảm: {{ itemsData[index].discountPercent }}% so với giá gốc
                                </p>

                                <p v-if="itemsData[index].isInvalid" class="mt-1 text-sm text-red-600 font-medium">
                                    * Giá sale đang cao hơn hoặc bằng giá gốc
                                </p>
                            </div>
                            <div>
                                <InputLabel>Số lượng bán<span class="text-red-500">*</span></InputLabel>
                                <NumberInput v-model="item.sale_quantity" />
                            </div>
                            <div>
                                <InputLabel>Mỗi khách được mua<span class="text-red-500">*</span></InputLabel>
                                <NumberInput v-model="item.user_limit" />
                            </div>
                        </div>

                        <button 
                            v-if="form.items.length > 1" 
                            @click="removeItem(index)" 
                            type="button" 
                            class="mt-2 text-xs text-red-500 font-bold"
                        >
                            XÓA SẢN PHẨM
                        </button>
                    </div>

                    <button type="button" @click="addItem" class="flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
                        + THÊM SẢN PHẨM VÀO ĐỢT FLASH SALE
                    </button>
                </ComponentCard>
            </div>
        </form>
    </AdminLayout>
</template>