<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    label: String,
    options: Array, 
    modelValue: Array, 
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');

const selectedItems = computed(() => {
    return props.options.filter(opt => props.modelValue.includes(opt.id));
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    return props.options.filter(opt => 
        opt.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const toggleOption = (id) => {
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(id);
    if (index > -1) {
        newValue.splice(index, 1);
    } else {
        newValue.push(id);
    }
    emit('update:modelValue', newValue);
};
const removeItem = (id) => {
    const newValue = props.modelValue.filter(itemId => itemId !== id);
    emit('update:modelValue', newValue);
};

const clearAll = () => {
    emit('update:modelValue', []);
};
</script>

<template>
    <div class="relative group h-10 flex items-center">
        <div class="flex items-center cursor-pointer group-hover:text-black transition-colors">
            <span class="text-[14px] font-semibold">
                {{ label }}
                <span v-if="modelValue.length > 0" class="text-black font-bold">({{ modelValue.length }})</span>
            </span>
            <i class="fas fa-chevron-down ml-2 text-[8px] transition-transform group-hover:rotate-180"></i>
        </div>

        <div class="absolute top-full left-0 mt-0 w-[800px] bg-white border border-gray-100 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-[60] flex flex-col rounded-b-sm">
            
            <div v-if="selectedItems.length > 0" class="p-4 bg-gray-50 border-b border-gray-100">
                <p class="text-[12px] font-black uppercase tracking-widest mb-3">Đã chọn</p>
                <div class="flex flex-wrap gap-2">
                    <div v-for="item in selectedItems" :key="item.id" 
                         class="flex items-center bg-white border border-gray-200 px-3 py-1 shadow-sm group/tag">
                        <span v-if="item.hex_code" 
                            class="w-5 h-5 rounded-full border border-gray-600 mr-2" 
                            :style="{ backgroundColor: item.hex_code }"></span>
                        <span class="text-[15px] font-semibold text-gray-800">{{ item.name }}</span>
                        <button @click="removeItem(item.id)" class="ml-2 text-gray-300 hover:text-red-500 transition-colors">
                            <i class="fas fa-times text-[12px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="relative mb-6">
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Tìm nhanh..."
                        class="w-full text-[15px] border-b border-gray-100 focus:border-black outline-none pb-2 pr-8 transition-colors"
                    />
                    <i class="fas fa-search absolute right-0 top-2 text-gray-300 text-[12px]"></i>
                </div>

                <div class="max-h-80 overflow-y-auto overflow-x-hidden custom-scrollbar pr-2">
                    <div class="grid grid-cols-3 gap-x-8 gap-y-2">
                        <div 
                            v-for="opt in filteredOptions" 
                            :key="opt.id" 
                            @click="toggleOption(opt.id)"
                            class="text-[15px] cursor-pointer transition-all hover:translate-x-1 pt-1 pb-3 flex items-center overflow-hidden"
                            :class="[
                                modelValue.map(String).includes(String(opt.id))
                                ? 'font-bold text-black underline underline-offset-[10px] decoration-2 decoration-black' 
                                : 'text-black hover:text-black'
                            ]"
                        >
                            <span 
                                v-if="opt.hex_code" 
                                class="w-6 h-6 rounded-full border border-gray-600 mr-2"
                                :style="{ backgroundColor: opt.hex_code }"
                            ></span>
                                {{ opt.name }}
                        </div>
                    </div>
                    
                    <div v-if="filteredOptions.length === 0" class="text-[14px] text-gray-400 italic py-4 text-center">
                        Không tìm thấy kết quả...
                    </div>
                </div>
            </div>

            <div v-if="modelValue.length > 0" class="p-4 border-t border-gray-50 flex justify-between items-center bg-gray-50/50">
                <button @click="clearAll" class="text-[12px] font-black uppercase text-red-500 hover:text-red-700 transition-colors">
                    Xóa tất cả
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f9f9f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #eee; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #ddd; }

/* Đảm bảo underline đẹp hơn */
.underline {
    text-underline-offset: 8px;
    text-decoration-thickness: 2px;
}
</style>