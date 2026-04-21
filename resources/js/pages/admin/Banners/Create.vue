<script setup>
import {useForm,Head} from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import FileInput from '@/components/admin/forms/FormElements/FileInput.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import SingleSelect from '@/components/admin/forms/FormElements/SingleSelect.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
const props = defineProps({
    positions: Array,
});

const form = useForm({
    title: '',
    subtitle: '',
    image: null,
    link_url: '',
    position: 'home_slider',
    order: 0,
    is_active: true,
});

const submit = () => {
    form.post(route('admin.banners.store'), {
        forceFormData: true,
        preserveScroll: true
    });
}
</script>
<template>
    <AdminLayout title="Thêm banner">
        <Head title="Thêm banner" />
        <PageBreadcrumb pageTitle="Thêm banner" parentName="Banner" :parentRoute="route('admin.banners.index')" />

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-6">
                    <ComponentCard title="Nội dung hiển thị">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Tiêu đề chính</InputLabel>
                                <Input v-model="form.title" placeholder="Nhập tiêu đề chính" :error="form.errors.title" />
                            </div>
                            <div>
                                <InputLabel>Tiêu đề phụ</InputLabel>
                                <Input v-model="form.subtitle" placeholder="Nhập tiêu đề phụ" :error="form.errors.subtitle"/>
                            </div>
                            <div>
                                <InputLabel>Đường dẫn liên kết (URL)</InputLabel>
                                <Input v-model="form.link_url" placeholder="Ví dụ: https://fashionecommerce.com/collection/abc" :error="form.errors.link_url" />
                            </div>
                        </div>
                    </ComponentCard>
                    <ComponentCard title="Hình ảnh banner">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Chọn ảnh <span class="text-red-500">*</span></InputLabel>
                                <FileInput 
                                    v-model="form.image" 
                                    :error="form.errors.image" 
                                />
                            </div>
                        </div>
                    </ComponentCard>
                    
                </div>
                <div class="space-y-6">
                    <ComponentCard title="Cài đặt hiển thị">
                        <div class="space-y-4">
                            <div>
                                <InputLabel>Vị trí hiển thị <span class="text-red-500">*</span></InputLabel>
                                <SingleSelect 
                                    v-model="form.position" 
                                    :options="positions" 
                                    option-label="label" 
                                    option-value="value" 
                                    placeholder="Chọn vị trí..."
                                    :error="form.errors.position"
                                />
                            </div>
                            
                            <div>
                                <InputLabel>Thứ tự ưu tiên</InputLabel>
                                <NumberInput v-model="form.order" placeholder="0" />
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <ToggleSwitch v-model="form.is_active" label="Trạng thái hoạt động" description="Bật để hiển thị Banner lên website."/>
                            </div>
                        </div>
                    </ComponentCard>
                    <SubmitButton :processing="form.processing" label="THÊM THƯƠNG HIỆU" loadingLabel="ĐANG LƯU..." />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>