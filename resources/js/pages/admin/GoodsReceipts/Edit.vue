<script setup>
import { useForm, Head, router } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';

import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
const props = defineProps({
    goodsreceipt: Object,
    suppliers: Array,
    variants: Array,
});

const isLocked = computed(() => ['completed', 'cancelled'].includes(props.goodsreceipt.receipt_status));

const form = useForm({
    _method: 'PUT',
    supplier_id: props.goodsreceipt.supplier_id,
    details: props.goodsreceipt.details.map(d => ({
        product_variant_id: d.product_variant_id,
        received_quantity: d.received_quantity,
        purchase_price: d.purchase_price,
    })),
    total_cost: props.goodsreceipt.total_cost
});
const isApproving = ref(false);
const isCancelling = ref(false);
const cancelReceipt = () => {
    router.post(route('admin.goodsreceipts.cancel', props.goodsreceipt.id), {}, {
        preserveScroll: true,
        onStart: () => isCancelling.value = true,
        onFinish: () => isCancelling.value = false,
    });
};
const approveReceipt = () => {
    router.post(route('admin.goodsreceipts.approve', props.goodsreceipt.id), {}, {
        preserveScroll: true,
        onStart: () => isApproving.value = true,
        onFinish: () => isApproving.value = false,
    });
};
watch(() => form.details, (newVal) => {
    form.total_cost = newVal.reduce((sum, item) => sum + (item.received_quantity * item.purchase_price), 0);
}, { deep: true });

const submit = () => {
    form.post(route('admin.goodsreceipts.update', props.goodsreceipt.id), {
        preserveScroll: true
    });
}

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Chờ xử lý',
        'completed': 'Đã nhập kho',
        'cancelled': 'Đã hủy'
    };
    return labels[status] || status;
};
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

const addRow = () =>{
    form.details.push({product_variant_id: null, received_quantity: 1, purchase_price: 0, sub_total: 0});
}

const removeRow = (index) =>{
    if (form.details.length > 1) {
        form.details.splice(index, 1);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Sửa phiếu nhập hàng" />
        <PageBreadcrumb pageTitle="Chỉnh sửa phiếu nhập" parentName="Phiếu nhập" :parentRoute="route('admin.goodsreceipts.index')" />
        <div :class="[
            'mb-6 p-4 rounded-xl border flex justify-between items-center shadow-sm',
            props.goodsreceipt.receipt_status === 'pending' ? 'bg-yellow-50 border-yellow-200' : 
            props.goodsreceipt.receipt_status === 'completed' ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'
        ]">
            <div class="flex items-center gap-4">
                <div>
                    <span class="text-xs text-gray-500 uppercase font-bold block mb-1">Trạng thái phiếu</span>
                    <span :class="['font-black text-lg', props.goodsreceipt.receipt_status === 'pending' ? 'text-yellow-700' : props.goodsreceipt.receipt_status === 'completed' ? 'text-green-600' : 'text-gray-700']">
                        {{ getStatusLabel(props.goodsreceipt.receipt_status) }}
                    </span>
                </div>
                <div class="h-10 w-px bg-gray-200 hidden md:block"></div>
                <div class="hidden md:block">
                    <span class="text-xs text-gray-500 uppercase font-bold block mb-1">Mã số phiếu</span>
                    <span class="font-mono font-bold text-gray-700">{{ props.goodsreceipt.receipt_code }}</span>
                </div>
            </div>

            <div class="flex gap-3">
                <div v-if="isLocked" class="flex items-center text-gray-400 italic text-sm bg-gray-100 px-4 py-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Phiếu đã được duyệt và không được chỉnh sửa nội dung. Lưu ý: chỉ có thể hủy khi chưa có sản phẩm nào được bán ra!
                </div>
                <ConfirmAction
                    v-if="props.goodsreceipt.receipt_status !== 'cancelled'"
                    key="cancel-action"
                    title="Hủy phiếu nhập"
                    :message="`Bạn có chắc muốn hủy phiếu ${props.goodsreceipt.receipt_code}?`"
                    confirmText="Xác nhận hủy"
                    :loading="isCancelling"
                    @confirm="cancelReceipt"
                >
                    <template #trigger>
                        <button type="button" class="px-5 py-2.5 bg-white border border-red-200 text-red-600 text-sm font-bold rounded-xl hover:bg-red-50 transition-all active:scale-95 shadow-sm">
                            HỦY PHIẾU
                        </button>
                    </template>
                </ConfirmAction>

                <ConfirmAction
                    v-if="props.goodsreceipt.receipt_status === 'pending'"
                    key="approve-action"
                    title="Duyệt phiếu nhập"
                    message="Hành động này sẽ chính thức nhập hàng vào kho và cập nhật số lượng tồn kho. Bạn chắc chắn chứ?"
                    confirmText="Xác nhận nhập kho"
                    :loading="isApproving"
                    @confirm="approveReceipt"
                >
                    <template #trigger>
                        <button type="button" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                            DUYỆT NHẬP KHO
                        </button>
                    </template>
                </ConfirmAction>
            </div>
        </div>

        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-800">Mã phiếu: {{ props.goodsreceipt.receipt_code }}</h2>
        </div>
        <form @submit.prevent="submit">
            <ComponentCard title="Thông tin chung">
                <div class="gap-4">
                    <div>
                        <InputLabel>Nhà cung cấp</InputLabel>
                        <SingleSelect v-model="form.supplier_id" :options="suppliers" option-label="supplier_name" option-value="id" :disabled="isLocked" clearable="false" :error="form.errors.supplier_id"/>
                    </div>
                </div>
            </ComponentCard>
            <ComponentCard title="Danh sách sản phẩm" class="mt-6">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="pb-2">Sản phẩm</th>
                            <th class="pb-2 w-32">Số lượng</th>
                            <th class="pb-2 w-40">Giá nhập</th>
                            <th class="pb-2 w-40">Thành tiền</th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in form.details" :key="index" class="border-b">
                            <td class="py-3 pr-2"><SingleSelect v-model="item.product_variant_id" :options="formattedVariants" option-label="display_name" option-value="id" :disabled="isLocked" /></td>
                            <td class="py-3 pr-2"><NumberInput v-model="item.received_quantity" :disabled="isLocked" placeholder="Nhập số lượng"/></td>
                            <td class="py-3 pr-2"><NumberInput v-model="item.purchase_price" :disabled="isLocked" placeholder="Nhập giá nhập" unit="VND"/></td>
                            <td class="py-3 font-bold">{{ (item.received_quantity * item.purchase_price).toLocaleString() }}đ</td>
                            <td v-if="!isLocked" class="py-3 text-right">
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
                <div v-if="!isLocked" class="mt-4">
                    <button @click="addRow" type="button" class="mt-4 flex items-center gap-2 text-blue-600 font-semibold hover:text-blue-800">
                        + THÊM SẢN PHẨM
                    </button>
                </div>
                <div class="mt-6 flex justify-between items-end">
                    <div class="ml-auto text-right">
                        <p class="text-lg font-bold text-gray-800">Tổng tiền:</p>
                        <p class="text-lg font-black text-blue-600">{{ form.total_cost.toLocaleString() }}đ</p>
                    </div>
                </div>
            </ComponentCard>

            <div class="mt-6 flex justify-end" v-if="!isLocked">
                <SubmitButton :processing="form.processing" label="LƯU PHIẾU NHẬP" loadingLabel="ĐANG LƯU..." />
            </div>
        </form>
    </AdminLayout>
</template>