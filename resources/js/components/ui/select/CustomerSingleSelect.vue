<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue'

const props = defineProps({
  options: { type: Array, required: true },
  error: { type: [String, Boolean], default: false },
  optionLabel: { type: String, default: 'name' }, // Đổi mặc định thành 'name' cho hợp với API tỉnh thành
  optionValue: { type: String, default: 'code' }, // Đổi mặc định thành 'code'
  placeholder: { type: String, default: 'Chọn...' },
  searchPlaceholder: { type: String, default: 'Tìm kiếm...' },
  clearable: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  searchable: { type: Boolean, default: true }
})

const modelValue = defineModel({ default: null })

const isOpen = ref(false)
const selectRef = ref(null)
const searchQuery = ref('')
const searchInput = ref(null) 

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) return props.options
  
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(opt => 
    String(opt[props.optionLabel]).toLowerCase().includes(query)
  )
})

const selectedOption = computed(() => {
  return props.options?.find(opt => opt[props.optionValue] === modelValue.value)
})

watch(isOpen, async (newVal) => {
  if (newVal && props.searchable) {
    await nextTick()
    searchInput.value?.focus()
  } else {
    searchQuery.value = ''
  }
})

const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

const selectItem = (item) => {
  modelValue.value = item[props.optionValue] 
  isOpen.value = false
}

const isSelected = (item) => {
  return modelValue.value === item[props.optionValue]
}

const handleClickOutside = (event) => {
  if (selectRef.value && !selectRef.value.contains(event.target)) isOpen.value = false
}

const clearValue = (event) => {
  event.stopPropagation()
  if (props.disabled) return
  modelValue.value = null
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div class="relative w-full" ref="selectRef">
    <div
      @click="toggleDropdown"
      :class="[
        'w-full flex items-center rounded-full border bg-white px-4 py-3 text-sm transition-all outline-none mt-2',
        disabled ? 'bg-gray-50 cursor-not-allowed opacity-70 border-gray-200' : 'cursor-pointer',
        error 
          ?
            'border-red-500 focus-within:ring-2 focus-within:ring-red-500/20' 
          :
            'border-gray-200 hover:border-primary/50 focus-within:ring-2 focus-within:ring-primary/20',
        isOpen && error ? 'ring-primary' : '',
        isOpen && !error ? 'ring-primary' : ''
      ]"
    >
      <div class="truncate flex-grow">
        <span v-if="!selectedOption" class="text-gray-400">{{ placeholder }}</span>
        <span v-else class="text-gray-800 font-medium">{{ selectedOption[optionLabel] }}</span>
      </div>
      
      <div class="ml-auto flex items-center gap-2 text-gray-400">
        <button 
          v-if="clearable && selectedOption && !disabled" 
          type="button" 
          @click.stop="clearValue"
          class="hover:text-red-500 transition-colors"
        >
          <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor">
            <path d="M15 5L5 15M5 5L15 15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>

        <svg 
          :class="{ 'rotate-180 text-primary': isOpen }" 
          class="transition-transform duration-300"
          width="16" height="16" viewBox="0 0 20 20" fill="none"
        >
          <path d="M4.79175 7.39551L10.0001 12.6038L15.2084 7.39551" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </div>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    > 
      <div v-if="isOpen && !disabled" class="absolute z-50 mt-2 w-full rounded-2xl border border-gray-100 bg-white shadow-xl overflow-hidden flex flex-col">
        
        <div v-if="searchable" class="p-3 border-b border-gray-50 bg-gray-50/30">
          <div class="relative">
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-full focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all"
              :placeholder="searchPlaceholder"
              @click.stop
            />
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <ul class="max-h-64 overflow-y-auto custom-scrollbar py-1">
          <li
            v-for="item in filteredOptions"
            :key="item[optionValue]"
            @click="selectItem(item)"
            class="group relative flex items-center w-full px-4 py-3 cursor-pointer transition-colors"
            :class="isSelected(item) ? 'bg-primary/5 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-50'"
          >
            <span class="grow truncate">{{ item[optionLabel] }}</span>
            <svg v-if="isSelected(item)" class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
          </li>
          <li v-if="filteredOptions.length === 0" class="px-4 py-8 text-center text-sm text-gray-400 italic">
            Không tìm thấy "{{ searchQuery }}"
          </li>
        </ul>
      </div>
    </transition>

    <p v-if="error" class="mt-1.5 ml-4 text-[12px] text-red-500 font-bold">
      * {{ error }}
    </p>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>