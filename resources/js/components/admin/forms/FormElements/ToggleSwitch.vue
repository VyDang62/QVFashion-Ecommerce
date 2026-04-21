<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    label: {
        type: String,
        default: 'Trạng thái'
    },
    description: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const proxyValue = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
});
</script>

<template>
    <label class="flex items-center gap-3 cursor-pointer group">
        <div class="relative inline-flex items-center">
            <input 
                type="checkbox" 
                v-model="proxyValue" 
                class="sr-only peer"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        </div>
        
        <div class="select-none">
            <span class="text-sm font-bold text-gray-700 group-hover:text-blue-600 transition-colors">
                {{ label }}
            </span>
            <p v-if="description" class="text-xs text-gray-500">
                {{ description }}
            </p>
        </div>
    </label>
</template>