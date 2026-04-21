<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { watch } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import Input from '@/components/ui/input/Input.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import ToggleSwitch from '@/components/admin/forms/FormElements/ToggleSwitch.vue';
import InputTextArea from '@/components/admin/forms/FormElements/InputTextArea.vue';

import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const form = useForm({
    title: '',
    slug: '',
    content: '',
    is_active: true,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
});

watch(() => form.title, (title) => {
    form.slug = title
        .toLowerCase()
        .normalize('NFD') 
        .replace(/[\u0300-\u036f]/g, '') 
        .replace(/[đĐ]/g, 'd')
        .replace(/([^0-9a-z-\s])/g, '') 
        .replace(/(\s+)/g, '-')
        .replace(/-+/g, '-') 
        .replace(/^-+|-+$/g, '');
});

const submit = () => {
    form.post(route('admin.pages.store'), {
        preserveScroll: true,
    });
};

const editorOptions = {
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['link', 'image', 'video'],
            ['clean']
        ]
    },
    placeholder: 'Soạn thảo nội dung trang...',
    theme: 'snow'
};
</script>

<template>
    <AdminLayout title="Thêm trang tĩnh">
        <Head title="Tạo trang mới" />
        <PageBreadcrumb pageTitle="Tạo trang mới" parentName="Trang tĩnh" :parentRoute="route('admin.pages.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-20">
            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thông tin cơ bản">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Tiêu đề trang <span class="text-red-500">*</span></InputLabel>
                            <Input 
                                v-model="form.title" 
                                placeholder="Ví dụ: Chính sách vận chuyển" 
                                :error="form.errors.title" 
                            />
                        </div>
                        <div>
                            <InputLabel>Đường dẫn (Slug) <span class="text-red-500">*</span></InputLabel>
                            <Input 
                                v-model="form.slug" 
                                placeholder="chinh-sach-van-chuyen" 
                                :error="form.errors.slug" 
                            />
                            <p class="mt-1 text-xs text-gray-600">Tự động sinh ra từ tiêu đề, có thể chỉnh sửa lại.</p>
                        </div>
                    </div>
                </ComponentCard>

                <ComponentCard title="Nội dung trang">
                    <div class="editor-container">
                        <QuillEditor 
                            v-model:content="form.content" 
                            contentType="html"
                            :options="editorOptions"
                        />
                    </div>
                    <p v-if="form.errors.content" class="mt-2 text-sm text-red-600 font-medium">{{ form.errors.content }}</p>
                </ComponentCard>
            </div>

            <div class="lg:col-span-4 space-y-6">
                <ComponentCard title="Trạng thái hiển thị">
                    <ToggleSwitch 
                        v-model="form.is_active" 
                        label="Kích hoạt trang" 
                        description="Nếu tắt, trang sẽ được lưu ở dạng bản nháp."
                    />
                </ComponentCard>

                <ComponentCard title="Tối ưu SEO">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Meta Title</InputLabel>
                            <Input 
                                v-model="form.meta_title" 
                                placeholder="Tiêu đề trên kết quả tìm kiếm" 
                                :error="form.errors.meta_title"
                            />
                        </div>
                        <div>
                            <InputLabel>Meta Description</InputLabel>
                            <InputTextArea 
                                v-model="form.meta_description" 
                                placeholder="Mô tả ngắn gọn nội dung trang" 
                                :error="form.errors.meta_description"
                            />
                        </div>
                        <div>
                            <InputLabel>Meta Keywords</InputLabel>
                            <Input 
                                v-model="form.meta_keywords" 
                                placeholder="Ví dụ: qvfashion, giao hàng, đổi trả" 
                                :error="form.errors.meta_keywords"
                            />
                        </div>
                    </div>
                </ComponentCard>

                <div class="sticky top-6">
                    <SubmitButton 
                        :processing="form.processing" 
                        label="TẠO TRANG" 
                        class="w-full shadow-lg shadow-blue-500/30" 
                    />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>