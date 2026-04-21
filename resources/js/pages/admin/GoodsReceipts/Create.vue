<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import { watch } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import { computed } from 'vue';
const props = defineProps({
    suppliers: Array,
    variants: Array,
});

const form = useForm({
    receipt_code: '',
    supplier_id: null,
    receipt_status: 'pending',
    details: [
        { product_variant_id: null, received_quantity: 1, purchase_price: 0, sub_total: 0 }
    ],
    total_cost: 0
});

const addRow = () =>{
    form.details.push({product_variant_id: null, received_quantity: 1, purchase_price: 0, sub_total: 0});
}

const removeRow = (index) =>{
    if (form.details.length > 1) {
        form.details.splice(index, 1);
    }
}

watch(() => form.details, (newDetails) => {
    let grandTotal = 0;
    newDetails.forEach(item => {
        item.sub_total = (item.received_quantity || 0) * (item.purchase_price || 0);
        grandTotal += item.sub_total;
    });
    form.total_cost = grandTotal;
}, {deep: true});

const formattedVariants = computed(() => {
    return props.variants.map(variant => {
        const attributes = variant.attribute_values
            ?.map(av => av.value)
            .join(' - ') || ' ';
        return {
            ...variant,
            display_name: `${variant.product.product_name} (${attributes})`,
            sub_label: `SKU: ${variant.sku} | Tồn: ${variant.stock_quantity}`,
        };
    });
})
const submit = () => {
    console.log("Dữ liệu gửi đi:", form.data());
    form.post(route('admin.goodsreceipts.store'), {
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Lập phiếu nhập hàng">
        <Head title="Nhập hàng" />
        <PageBreadcrumb pageTitle="Tạo phiếu nhập" parentName="Phiếu nhập" :parentRoute="route('admin.goodsreceipts.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
                <div class="lg:col-span-8  space-y-6">
                    <ComponentCard title="Thông tin phiếu nhập">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Nhà cung cấp <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect v-model="form.supplier_id" :options="suppliers" option-label="supplier_name" option-value="id" :error="form.errors.supplier_id"/>
                            </div>
                            <div>
                                <InputLabel>Trạng thái phiếu<span class="text-red-500">*</span></InputLabel>
                                <select v-model="form.receipt_status" class="w-full rounded-lg border-gray-300">
                                    <option value="pending">Chờ xử lý</option>
                                    <option value="completed">Đã hoàn thành</option>
                                </select>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-500">Tổng tiền giá trị phiếu:</p>
                                <p class="text-2xl font-bold text-blue-600">{{ form.total_cost.toLocaleString() }}đ</p>
                            </div>
                        </div>
                    </ComponentCard>
                    
                </div>
                <div class="lg:col-span-8 space-y-6">
                    <ComponentCard title="Danh sách sản phẩm nhập">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3">Sản phẩm/ Biến thể</th>
                                    <th class="pb-3 w-24">Số lượng</th>
                                    <th class="pb-3 w-40">Giá nhập</th>
                                    <th class="pb-3 w-40">Thành tiền</th>
                                    <th class="pb-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.details" :key="index" class="border-b">
                                    <td class="py-3 pr-4">
                                        <SingleSelect v-model="item.product_variant_id" :options="formattedVariants" option-label="display_name" option-value="id" placeholder="Chọn biến thể" :error="form.errors[`details.${index}.product_variant_id`]">
                                            <template #option="{ option }">
                                                <div class="flex flex-col py-1">
                                                    <span class="font-bold text-gray-800">{{ option.display_name }}</span>
                                                    <div class="flex justify-between text-[12px] text-gray-400 mt-0.5">
                                                        <span>Mã: {{ option.sku }}</span>
                                                        <span class="text-blue-500 font-medium">Hiện có: {{ option.stock_quantity }} cái</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </SingleSelect>
                                    </td>
                                    <td class="py-3 pr-2">
                                        <NumberInput v-model="item.received_quantity" min="1"/>
                                    </td>
                                    <td class="py-3 pr-2">
                                        <NumberInput v-model="item.purchase_price" min="0" unit="VND"/>
                                    </td>
                                    <td class="py-3 font-medium">
                                        {{ item.sub_total.toLocaleString() }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <button
                                            @click="removeRow(index)" 
                                            type="button" 
                                            class="text-red-500 hover:text-red-700"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button @click="addRow" type="button" class="mt-4 flex items-center gap-2 text-blue-600 font-semibold hover:text-blue-800">
                            + THÊM SẢN PHẨM
                        </button>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="LƯU PHIẾU NHẬP" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>