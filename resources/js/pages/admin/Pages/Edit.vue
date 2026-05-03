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
import "@vueup/vue-quill/dist/vue-quill.snow.css";

const props = defineProps({
    page: Object,
});

const form = useForm({
    _method: 'PUT',
    title: props.page.title,
    slug: props.page.slug,
    content: props.page.content,
    is_active: props.page.is_active,
    meta_title: props.page.meta_title,
    meta_description: props.page.meta_description,
    meta_keywords: props.page.meta_keywords,
});

const submit = () => {
    form.post(route('admin.pages.update', props.page.id), {
        preserveScroll: true,
    });
};

watch(() => form.title, (title) => {
    form.slug = title.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
});

const editorOptions = {
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ color: [] }, { background: [] }],
            ['link', 'image', 'video'],
            ['clean']
        ]
    },
    placeholder: 'Viết nội dung trang tại đây...',
    theme: 'snow'
};
</script>

<template>
    <AdminLayout title="Sửa trang tĩnh">
        <Head :title="'Sửa trang: ' + page.title" />
        <PageBreadcrumb pageTitle="Sửa trang tĩnh" parentName="Trang tĩnh" :parentRoute="route('admin.pages.index')" />

        <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-20">
            <div class="lg:col-span-8 space-y-6">
                <ComponentCard title="Thông tin cơ bản">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Tiêu đề trang <span class="text-red-500">*</span></InputLabel>
                            <Input v-model="form.title" placeholder="Ví dụ: Chính sách bảo mật" :error="form.errors.title" />
                        </div>
                        <div>
                            <InputLabel>Đường dẫn (Slug) <span class="text-red-500">*</span></InputLabel>
                            <Input v-model="form.slug" placeholder="chinh-sach-bao-mat" :error="form.errors.slug" />
                        </div>
                    </div>
                </ComponentCard>

                <ComponentCard title="Nội dung trang">
                    <div class="min-h-[400px]">
                        <QuillEditor 
                            v-model:content="form.content" 
                            contentType="html"
                            :options="editorOptions"
                        />
                    </div>
                    <p v-if="form.errors.content" class="mt-2 text-sm text-red-600">{{ form.errors.content }}</p>
                </ComponentCard>
            </div>

            <div class="lg:col-span-4 space-y-6">
                <ComponentCard title="Trạng thái">
                    <ToggleSwitch 
                        v-model="form.is_active" 
                        label="Công khai trang" 
                        description="Bật để khách hàng có thể xem trang này trên website."
                    />
                </ComponentCard>

                <ComponentCard title="Cấu hình SEO">
                    <div class="space-y-4">
                        <div>
                            <InputLabel>Meta Title</InputLabel>
                            <Input v-model="form.meta_title" placeholder="Mặc định lấy tiêu đề trang" />
                        </div>
                        <div>
                            <InputLabel>Meta Description</InputLabel>
                            <InputTextArea v-model="form.meta_description" placeholder="Mô tả ngắn cho Google..." />
                        </div>
                        <div>
                            <InputLabel>Meta Keywords</InputLabel>
                            <Input v-model="form.meta_keywords" placeholder="Cách nhau bằng dấu phẩy" />
                        </div>
                    </div>
                </ComponentCard>

                <div class="sticky top-6">
                    <SubmitButton :processing="form.processing" label="CẬP NHẬT TRANG TĨNH" class="w-full" />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
