<script setup>
import { computed } from 'vue';

const props = defineProps({
    options: Array,
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);

const currentLabel = computed(() => {
    const active = props.options.find(opt => opt.id === props.modelValue);
    return active ? active.name : 'Sắp xếp';
});

const selectSort = (id) => {
    emit('update:modelValue', id);
};
</script>

<template>
    <div class="relative group h-10 flex items-center">
        <div class="flex items-center cursor-pointer group-hover:text-black transition-colors">
            <span class="text-[14px] font-semibold">
                {{ currentLabel }}
            </span>
            <i class="fas fa-chevron-down ml-2 text-[8px] transition-transform group-hover:rotate-180"></i>
        </div>

        <div class="absolute top-full right-0 mt-0 w-56 bg-white border border-gray-100 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-[70] py-4">
            <div 
                v-for="opt in options" 
                :key="opt.id" 
                @click="selectSort(opt.id)"
                class="px-6 py-2 text-[13px] cursor-pointer transition-all hover:bg-gray-50"
                :class="modelValue === opt.id ? 'font-bold text-black' : 'text-gray-500'"
            >
                {{ opt.name }}
            </div>
        </div>
    </div>
</template>