<template>
    <Transition
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="-translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div 
            v-if="modelValue"
            :class="['relative rounded-xl border p-4 shadow-sm mb-4', variantClasses[variant].container]"
        >
            <div class="flex items-start gap-3">
                <div :class="['flex-shrink-0', variantClasses[variant].iconColor]">
                    <component :is="icons[variant]" class="w-5 h-5" />
                </div>

                <div class="flex-1 min-w-0">
                    <h4 v-if="title" :class="['text-sm font-bold mb-1', variantClasses[variant].textColor]">
                        {{ title }}
                    </h4>
                    <p :class="['text-sm leading-relaxed', variantClasses[variant].textColor, title ? 'opacity-90' : 'font-medium']">
                        {{ message }}
                    </p>

                    <Link
                        v-if="showLink"
                        :href="linkHref"
                        class="inline-block mt-2 text-sm font-bold underline hover:opacity-70 transition-opacity"
                        :class="variantClasses[variant].textColor"
                    >
                        {{ linkText }}
                    </Link>
                </div>

                <button 
                    v-if="closable"
                    @click="close" 
                    class="p-1 rounded-lg transition-colors hover:bg-black/5 flex-shrink-0"
                    :class="variantClasses[variant].iconColor"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { SuccessIcon, ErrorIcon, WarningIcon, InfoCircleIcon } from '@/icons'

interface AlertProps {
    modelValue?: boolean
    variant: 'success' | 'error' | 'warning' | 'info'
    title?: string
    message: string
    showLink?: boolean
    linkHref?: string
    linkText?: string
    closable?: boolean
}

const props = withDefaults(defineProps<AlertProps>(), {
    modelValue: true,
    showLink: false,
    linkHref: '#',
    linkText: 'Xem chi tiết',
    closable: true,
    title: ''
});

const emit = defineEmits(['update:modelValue', 'close']);

const close = () => {
    emit('update:modelValue', false);
    emit('close');
};

const variantClasses = {
    success: {
        container: 'bg-green-50 border-green-200',
        iconColor: 'text-green-600',
        textColor: 'text-green-800',
    },
    error: {
        container: 'bg-red-50 border-red-200',
        iconColor: 'text-red-600',
        textColor: 'text-red-800',
    },
    warning: {
        container: 'bg-amber-50 border-amber-200',
        iconColor: 'text-amber-600',
        textColor: 'text-amber-800',
    },
    info: {
        container: 'bg-blue-50 border-blue-200',
        iconColor: 'text-blue-600',
        textColor: 'text-blue-800',
    },
}

const icons = {
  success: SuccessIcon,
  error: ErrorIcon,
  warning: WarningIcon,
  info: InfoCircleIcon,
}
</script>