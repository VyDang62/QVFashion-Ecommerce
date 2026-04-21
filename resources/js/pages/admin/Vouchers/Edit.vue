<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import MultipleSelect from '@/components/admin/forms/FormElements/MultipleSelect.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const props = defineProps({
    voucher: Object,
    voucherTypes: Array,
    brands: Array,
    categories: Array,
    products: Array,
});

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
};


const form = useForm({
    _method: 'PUT',
    code: props.voucher.code,
    voucher_type: props.voucher.voucher_type,
    discount_value: props.voucher.discount_value,
    max_discount_amount: props.voucher.max_discount_amount,
    min_order_value: props.voucher.min_order_value,
    usage_limit: props.voucher.usage_limit,
    per_user_limit: props.voucher.per_user_limit,
    start_date: formatDateTime(props.voucher.start_date),
    end_date: formatDateTime(props.voucher.end_date),
    is_active: Boolean(props.voucher.is_active),
    brand_ids: props.voucher.restrictions
        ? props.voucher.restrictions
            .filter(r => r.restrict_type === 'brand')
            .map(r => r.restrict_id)
        : [],
    category_ids: props.voucher.restrictions
        ? props.voucher.restrictions
            .filter(r => r.restrict_type === 'category')
            .map(r => r.restrict_id)
        : [],
    product_ids: props.voucher.restrictions
        ? props.voucher.restrictions
            .filter(r => r.restrict_type === 'product')
            .map(r => r.restrict_id)
        : [],
});

const submit = () => {
    form.post(route('admin.vouchers.update', props.voucher.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Sửa Voucher">
        <Head :title="`Sửa Voucher: ${voucher.code}`" />
        <PageBreadcrumb :pageTitle="`Chỉnh sửa: ${voucher.code}`" parentName="Mã giảm giá" :parentRoute="route('admin.vouchers.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-6">
                    <ComponentCard title="Thông tin giảm giá">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Mã Voucher <span class="text-red-500">*</span></InputLabel>
                                <Input v-model="form.code" class="uppercase" :error="form.errors.code" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel>Loại giảm giá</InputLabel>
                                    <SingleSelect 
                                        v-model="form.voucher_type" 
                                        :options="voucherTypes" 
                                        option-label="label" 
                                        option-value="value" 
                                    />
                                </div>
                                <div>
                                    <InputLabel>Giá trị giảm</InputLabel>
                                    <NumberInput v-model="form.discount_value" :placeholder="form.voucher_type === 'percentage' ? '%' : 'VNĐ'" />
                                </div>
                            </div>

                            <div v-if="form.voucher_type === 'percentage'">
                                <InputLabel>Số tiền giảm tối đa (VNĐ)</InputLabel>
                                <NumberInput v-model="form.max_discount_amount" />
                            </div>

                            <div>
                                <InputLabel>Giá trị đơn hàng tối thiểu (VNĐ)</InputLabel>
                                <NumberInput v-model="form.min_order_value" />
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Giới hạn áp dụng">
                        <div class="space-y-5">
                            <div>
                                <InputLabel>Áp dụng cho Thương hiệu</InputLabel>
                                <MultipleSelect 
                                    v-model="form.brand_ids" 
                                    :options="brands" 
                                    option-label="brand_name" 
                                    option-value="id" 
                                    placeholder="Chọn thương hiệu..."
                                />
                            </div>

                            <div>
                                <InputLabel>Áp dụng cho Danh mục con</InputLabel>
                                <MultipleSelect 
                                    v-model="form.category_ids" 
                                    :options="categories" 
                                    option-label="category_name" 
                                    option-value="id" 
                                    placeholder="Chọn danh mục con..."
                                />
                            </div>
                            <div>
                                <InputLabel>Áp dụng cho Sản phẩm</InputLabel>
                                <MultipleSelect 
                                    v-model="form.product_ids" 
                                    :options="products" 
                                    option-label="product_name" 
                                    option-value="id" 
                                    placeholder="Chọn sản phẩm..."
                                />
                            </div>
                        </div>
                    </ComponentCard>
                </div>

                <div class="space-y-6">
                    <ComponentCard title="Thời gian & Giới hạn">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel>Tổng lượt dùng</InputLabel>
                                    <NumberInput v-model="form.usage_limit" placeholder="Không giới hạn" />
                                </div>
                                <div>
                                    <InputLabel>Lượt dùng/Khách</InputLabel>
                                    <NumberInput v-model="form.per_user_limit" />
                                </div>
                            </div>
                            <div>
                                <InputLabel>Ngày bắt đầu</InputLabel>
                                <input type="datetime-local" v-model="form.start_date" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm" />
                            </div>
                            <div>
                                <InputLabel>Ngày kết thúc</InputLabel>
                                <input type="datetime-local" v-model="form.end_date" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm" />
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Trạng thái">
                        <ToggleSwitch 
                            v-model="form.is_active" 
                            label="Kích hoạt mã" 
                            description="Tắt để tạm ngưng sử dụng mã này trên toàn hệ thống."
                        />
                    </ComponentCard>

                    <SubmitButton :processing="form.processing" label="CẬP NHẬT VOUCHER" loadingLabel="ĐANG LƯU..." />
                </div>
        </form>
    </AdminLayout>
</template>