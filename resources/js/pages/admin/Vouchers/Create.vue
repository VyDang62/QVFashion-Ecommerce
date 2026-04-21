<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import MultipleSelect from '@/components/admin/forms/FormElements/MultipleSelect.vue';

const props = defineProps({
    voucherTypes: Array,
    brands: Array,
    categories: Array,
    products: Array,
});

const form = useForm({
    code: '',
    voucher_type: '',
    discount_value: 0,
    max_discount_amount: null,
    min_order_value: 0,
    usage_limit: null,
    per_user_limit: 1,
    start_date: '',
    end_date: '',
    is_active: true,
    brand_ids: [],
    category_ids: [],
    product_ids: [],
});

const submit = () => {
    form.post(route('admin.vouchers.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Tạo Mã giảm giá">
        <Head title="Tạo mã giảm giá" />
        <PageBreadcrumb pageTitle="Tạo Mã giảm giá mới" parentName="Mã giảm giá" :parentRoute="route('admin.vouchers.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <ComponentCard title="Thông tin mã giảm giá">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Mã Voucher <span class="text-red-500">*</span></InputLabel>
                                <Input v-model="form.code" placeholder="Ví dụ: VOUCHER2026" class="uppercase" :error="form.errors.code" />
                                <p class="mt-1 text-[14px] text-gray-600 italic">Khách hàng sẽ nhập mã này khi thanh toán.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4 ">
                                <div>
                                    <InputLabel>Loại giảm giá <span class="text-red-500">*</span></InputLabel>
                                    <SingleSelect 
                                        v-model="form.voucher_type" 
                                        :options="voucherTypes" 
                                        option-label="label" 
                                        option-value="value"
                                        placeholder="Chọn loại giảm giá..."
                                    />
                                </div>
                                <div>
                                    <InputLabel>Giá trị giảm <span class="text-red-500">*</span></InputLabel>
                                    <NumberInput v-model="form.discount_value" :placeholder="form.voucher_type === 'percentage' ? '%' : 'VNĐ'" />
                                </div>
                            </div>

                            <div v-if="form.voucher_type === 'percentage'">
                                <InputLabel>Số tiền giảm tối đa (VNĐ)</InputLabel>
                                <NumberInput v-model="form.max_discount_amount" placeholder="Ví dụ: 50,000" />
                            </div>

                            <div>
                                <InputLabel>Giá trị đơn hàng tối thiểu (VNĐ) <span class="text-red-500">*</span></InputLabel>
                                <NumberInput v-model="form.min_order_value" placeholder="Ví dụ: 200,000" />
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Giới hạn áp dụng">
                        <p class="text-[14px] text-gray-600 italic">Nếu không chọn, Voucher sẽ áp dụng cho tất cả sản phẩm.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel>Áp dụng cho thương hiệu</InputLabel>
                                <MultipleSelect 
                                    v-model="form.brand_ids" 
                                    :options="brands" 
                                    option-label="brand_name" 
                                    option-value="id" 
                                    placeholder="Chọn thương hiệu..."
                                />
                            </div>
                            <div>
                                <InputLabel>Áp dụng cho Danh mục</InputLabel>
                                <MultipleSelect 
                                    v-model="form.category_ids" 
                                    :options="categories" 
                                    option-label="category_name" 
                                    option-value="id" 
                                    placeholder="Chọn danh mục..."
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
                                    <InputLabel>Tổng lượt sử dụng</InputLabel>
                                    <NumberInput v-model="form.usage_limit" placeholder="Không giới hạn" />
                                </div>
                                <div>
                                    <InputLabel>Mỗi khách được dùng <span class="text-red-500">*</span></InputLabel>
                                    <NumberInput v-model="form.per_user_limit" />

                                </div>
                            </div>
                            <div>
                                <InputLabel>Ngày bắt đầu</InputLabel>
                                <input type="datetime-local" v-model="form.start_date" class="w-full p-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block" />
                                <p v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</p>
                            </div>
                            <div>
                                <InputLabel>Ngày kết thúc</InputLabel>
                                <input type="datetime-local" v-model="form.end_date" class="w-full p-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block" />
                                <p v-if="form.errors.end_date" class="text-red-500 text-xs mt-1">{{ form.errors.end_date }}</p>
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Trạng thái">
                        <ToggleSwitch 
                            v-model="form.is_active" 
                            label="Kích hoạt mã" 
                            description="Cho phép khách hàng sử dụng mã này ngay lập tức."
                        />
                    </ComponentCard>

                    <SubmitButton :processing="form.processing" label="TẠO VOUCHER" loadingLabel="ĐANG TẠO..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>