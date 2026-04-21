<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';

const form = useForm({
    attribute_name: '',
    values: [
        { value: '', hex_code: null } 
    ]
});

const addValue = () => {
    form.values.push('');
};

const removeValue = (index) => {
    if (form.values.length > 1) {
        form.values.splice(index, 1);
    }
};
const formatHex = (index) => {
    let value = form.values[index].hex_code;
    
    if (value) {
        if (!value.startsWith('#')) {
            value = '#' + value;
        }
        
        value = '#' + value.replace(/[^0-9A-Fa-f]/g, '').substring(0, 6);
        
        form.values[index].hex_code = value.toUpperCase();
    }
};

const submit = () => {
    form.post(route('admin.attributes.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AdminLayout title="Thêm thuộc tính">
        <Head title="Thêm thuộc tính" />
        <PageBreadcrumb pageTitle="Thêm thuộc tính" parentName="Thuộc tính" :parentRoute="route('admin.attributes.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 gap-6">
                <div class="lg:col-span-8  space-y-6">
                    <ComponentCard title="Thông tin thuộc tính">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Tên thuộc tính <span class="text-red-500">*</span></InputLabel>
                                <Input 
                                    v-model="form.attribute_name" 
                                    placeholder="Ví dụ: Màu sắc, Kích thước, Chất liệu..." 
                                    :error="form.errors.attribute_name" 
                                />
                            </div>
                        </div>
                    </ComponentCard>

                    <ComponentCard title="Giá trị thuộc tính">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 font-semibold text-gray-700">Giá trị <span class="text-red-500">*</span></th>
                                    <th class="pb-3 font-semibold text-gray-700 w-40">Mã màu (Nếu có)</th>
                                    <th class="pb-3 w-20 text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.values" :key="index" class="border-b last:border-0">
                                    <td class="py-3 pr-4">
                                        <Input 
                                            v-model="item.value" 
                                            :placeholder="'Nhập giá trị...'" 
                                            :error="form.errors[`values.${index}.value`]" 
                                        />
                                    </td>
                                    
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2 group">
                                            <div class="relative w-10 h-10 shrink-0 overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                                                <input 
                                                    type="color" 
                                                    v-model="item.hex_code" 
                                                    class="absolute -inset-2 w-14 h-14 cursor-pointer"
                                                />
                                            </div>

                                            <div class="flex-1">
                                                <Input 
                                                    v-model="item.hex_code" 
                                                    placeholder="#FFFFFF" 
                                                    maxlength="7"
                                                    class="uppercase font-mono"
                                                    @input="formatHex(index)"
                                                    :error="form.errors[`values.${index}.hex_code`]"
                                                />
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3 text-right">
                                        <button 
                                            @click="removeValue(index)" 
                                            type="button" 
                                            v-if="form.values.length > 1"
                                            class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <button 
                            @click="addValue" 
                            type="button" 
                            class="mt-4 flex items-center gap-2 text-blue-600 font-semibold hover:text-blue-800 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            THÊM GIÁ TRỊ
                        </button>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="LƯU THUỘC TÍNH" loadingLabel="ĐANG LƯU..." />
                    
                </div>
            </div>
        </form>
    </AdminLayout>
</template>