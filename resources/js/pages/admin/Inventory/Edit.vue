<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import { useFormatter } from '@/composables/useFormatter';
import InputTextArea from '@/components/admin/forms/FormElements/InputTextArea.vue';

const props = defineProps({
    batch: Object,
});

const { formatPrice, formatDateTime } = useFormatter();

const form = useForm({
    new_quantity: props.batch.remaining_quantity,
    reason: '',
});

const submit = () => {
    form.patch(route('admin.inventory.batches.adjust', props.batch.id), {
        preserveScroll: true,
        onSuccess: () => {
        },
    });
};
</script>

<template>
    <Head :title="'Điều chỉnh lô: ' + batch.batch_code" />
    <AdminLayout>
        <PageBreadcrumb 
            :pageTitle="'Điều chỉnh lô hàng ' + batch.batch_code" 
            parentName="Lô hàng" 
            :parentRoute="route('admin.inventory.batches')" 
        />

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="lg:col-span-4 space-y-6">
                <ComponentCard title="Thông tin lô hàng">
                    <div class="space-y-4 text-sm">
                        <div class="pb-3 border-b border-dashed border-gray-100">
                            <p class="text-md font-bold text-gray-600">Sản phẩm</p>
                            <p class="font-bold text-gray-800 mt-1">{{ batch.variant?.product?.product_name }}</p>
                            <p class="text-xs text-blue-600 font-mono">{{ batch.variant?.sku }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-md font-bold text-gray-600">Số lượng gốc</p>
                                <p class="font-black text-gray-800">{{ batch.original_quantity }} cái</p>
                            </div>
                            <div>
                                <p class="text-md font-bold text-gray-600">Giá nhập</p>
                                <p class="font-black text-green-600">{{ formatPrice(batch.purchase_price) }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-md font-bold text-gray-600">Ngày nhập</p>
                            <p class="font-medium text-gray-700">{{ formatDateTime(batch.received_date) }}</p>
                        </div>
                    </div>
                </ComponentCard>
            </div>

            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thực hiện điều chỉnh">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel>Số lượng thực tế mới</InputLabel>
                                <Input 
                                    type="number" 
                                    v-model="form.new_quantity" 
                                    :error="form.errors.new_quantity" 
                                    placeholder="Nhập số lượng thực tế..."
                                />
                                <p class="mt-2 text-xs text-red-500 font-medium">
                                    * Tối đa: {{ batch.original_quantity }}
                                </p>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-2xl flex flex-col justify-center border border-gray-200">
                                <p class="text-xs text-gray-500 font-bold">Chênh lệch</p>
                                <p :class="['text-md font-black mt-1', (form.new_quantity - batch.remaining_quantity) >= 0 ? 'text-emerald-600' : 'text-red-500']">
                                    {{ (form.new_quantity - batch.remaining_quantity) > 0 ? '+' : '' }}{{ form.new_quantity - batch.remaining_quantity }} cái
                                </p>
                            </div>
                        </div>

                        <div>
                            <InputLabel>Lý do điều chỉnh</InputLabel>
                            <InputTextArea 
                                v-model="form.reason"
                                placeholder="Ghi rõ lý do (ví dụ: hàng lỗi, hư hỏng...)"
                            />
                            <div v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</div>
                        </div>
                    </div>
                </ComponentCard>

                <div class="flex justify-end gap-3">
                    <Link 
                        :href="route('admin.inventory.batches')" 
                        class="px-8 py-3 bg-white text-gray-600 font-bold rounded-2xl border border-gray-200 hover:bg-gray-50 transition-all text-sm"
                    >
                        HỦY BỎ
                    </Link>
                    <SubmitButton 
                        :processing="form.processing" 
                        label="LƯU ĐIỀU CHỈNH" 
                        loadingLabel="ĐANG CẬP NHẬT..." 
                        class="px-10"
                    />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>