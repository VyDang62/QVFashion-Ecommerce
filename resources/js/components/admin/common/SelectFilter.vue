<script setup>
defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Tất cả'
    },
    label: String,
    optionLabel: {
        type: String,
        default: 'label'
    },
    optionValue: {
        type: String,
        default: 'value'
    }
});

defineEmits(['update:modelValue']);
</script>
<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="text-sm font-medium text-gray-700">
            {{ label }}
        </label>
        
        <select 
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="block w-full px-3 py-2 text-sm bg-white border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-colors cursor-pointer"
        >
            <option value="">{{ placeholder }}</option>
            <option 
                v-for="option in options" 
                :key="option[optionValue]" 
                :value="option[optionValue]"
            >
                {{ option[optionLabel] }}
            </option>
        </select>
    </div>
</template>