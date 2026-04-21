<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import InputLabel from '@/components/ui/label/InputLabel.vue';
import SubmitButton from '@/components/admin/forms/FormElements/SubmitButton.vue';
import FileInput from '@/components/admin/forms/FormElements/FileInput.vue';
import Input from '@/components/ui/input/Input.vue';
import InputTextArea from '@/components/admin/forms/FormElements/InputTextArea.vue';
import NumberInput from '@/components/ui/input/NumberInput.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    settingsGroups: Object,
});

const initialData = {};
Object.values(props.settingsGroups).flat().forEach(setting => {
    if (setting.type === 'boolean') {
        initialData[setting.key] = Boolean(Number(setting.value));
    } else {
        initialData[setting.key] = setting.value;
    }
});

const form = useForm({
    settings: initialData,
    _method: 'PUT',
});

const groups = Object.keys(props.settingsGroups);
const activeTab = ref(groups[0] || 'general');
const logoPreview = ref(null);

const handleFileChange = (e, key) => {
    const file = e.target.files[0];
    if (file) {
        form.settings[key] = file;
        logoPreview.value = URL.createObjectURL(file); 
    }
};

const submit = () => {
    form.post(route('admin.settings.update'), {
        forceFormData: true, 
        preserveScroll: true,
        onSuccess: () => {
            logoPreview.value = null;
        }
    });
};
</script>

<template>
    <Head title="Cấu hình hệ thống" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Cấu hình hệ thống" />

        <div class="space-y-6">
            <div class="flex gap-6 border-b border-gray-200">
                <button 
                    v-for="group in groups" :key="group"
                    @click="activeTab = group"
                    :class="activeTab === group ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="pb-4 px-1 border-b-2 font-bold text-sm uppercase transition-all"
                >
                    {{ group }}
                </button>
            </div>

            <form @submit.prevent="submit">
                <ComponentCard :title="'Cài đặt nhóm: ' + activeTab">
                    <div class="space-y-8">
                        <div v-for="setting in settingsGroups[activeTab]" :key="setting.id" 
                             class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start border-b border-gray-50 pb-6 last:border-0">
                            
                            <div class="space-y-1">
                                <InputLabel class="text-base font-semibold text-gray-800 uppercase tracking-wider">
                                    {{ setting.key.replace(/_/g, ' ') }}
                                </InputLabel>
                                <p class="text-[14px] text-gray-600 italic">{{ setting.description }}</p>
                            </div>

                            <div class="md:col-span-2">
                                
                                <div v-if="setting.key === 'website_logo'" class="space-y-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-24 h-24 border rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center">
                                            <img v-if="logoPreview || setting.value" 
                                                 :src="logoPreview || '/storage/' + setting.value" 
                                                 class="max-w-full max-h-full object-contain" />
                                            <span v-else class="text-gray-300 text-xs">No Logo</span>
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" @change="(e) => handleFileChange(e, setting.key)" 
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="setting.type === 'boolean'" class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.settings[setting.key]" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">{{ form.settings[setting.key] ? 'Bật' : 'Tắt' }}</span>
                                    </label>
                                </div>
                                <NumberInput v-else-if="setting.type === 'integer'" v-model="form.settings[setting.key]"/>
                                <InputTextArea v-else-if="setting.type === 'json' || setting.type === 'array'" v-model="form.settings[setting.key]"/>
                                <Input v-else v-model="form.settings[setting.key]" />
                            
                            </div>
                        </div>
                    </div>
                </ComponentCard>

                <div class="mt-8 flex justify-end">
                    <SubmitButton :processing="form.processing" label="CẬP NHẬT CẤU HÌNH" />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>