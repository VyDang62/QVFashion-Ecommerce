<script setup>
import { onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: { type: String, required: true },
    loading: { type: Boolean, default: false },
    confirmText: { type: String, default: 'Xác nhận' },
    variant: { type: String, default: 'danger' } // danger | primary | success
});

const emit = defineEmits(['close', 'confirm']);

const themeConfig = computed(() => {
    const themes = {
        danger: {
            iconBg: 'bg-red-100',
            iconColor: 'text-red-600',
            btnBg: 'bg-red-600 hover:bg-red-700 shadow-red-500/30',
            iconPath: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
        },
        primary: {
            iconBg: 'bg-blue-100',
            iconColor: 'text-blue-600',
            btnBg: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/30',
            iconPath: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'
        },
        success: {
            iconBg: 'bg-green-100',
            iconColor: 'text-green-600',
            btnBg: 'bg-green-600 hover:bg-green-700 shadow-green-500/30',
            iconPath: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        }
    };
    return themes[props.variant] || themes.danger;
});

const handleEsc = (e) => {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
};

onMounted(() => window.addEventListener('keydown', handleEsc));
onUnmounted(() => window.removeEventListener('keydown', handleEsc));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                    
                    <div :class="[themeConfig.iconBg]" class="mx-auto flex h-12 w-12 items-center justify-center rounded-full mb-4">
                        <svg :class="[themeConfig.iconColor]" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="themeConfig.iconPath" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-bold text-center text-gray-900">{{ title }}</h3>
                    <p class="mt-2 text-sm text-center text-gray-500">{{ message }}</p>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                        <button 
                            @click="emit('close')"
                            type="button"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            Hủy bỏ
                        </button>
                        
                        <button 
                            @click="emit('confirm')"
                            type="button"
                            :disabled="loading"
                            :class="[themeConfig.btnBg]"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-xl disabled:opacity-50 flex items-center justify-center gap-2 transition-all shadow-lg"
                        >
                            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            {{ loading ? 'Đang xử lý...' : confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>