<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue'

const props = defineProps({
  options: { type: Array, required: true },
  error: { type: [String, Boolean], default: false },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  placeholder: { type: String, default: 'Chọn nhiều...' },
  searchPlaceholder: { type: String, default: 'Tìm kiếm...' }, // Prop mới
  disabled: { type: Boolean, default: false },
  searchable: { type: Boolean, default: true } // Cho phép bật/tắt search
})

const modelValue = defineModel({ default: () => [] })

const isOpen = ref(false)
const selectRef = ref(null)
const searchQuery = ref('')
const searchInput = ref(null)

// Logic lọc danh sách dựa trên từ khóa tìm kiếm
const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) return props.options
  
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(opt => 
    String(opt[props.optionLabel]).toLowerCase().includes(query)
  )
})

const selectedOptions = computed(() => {
  if (!Array.isArray(modelValue.value)) return []
  return props.options.filter(opt => modelValue.value.includes(opt[props.optionValue]))
})

// Focus vào ô search khi mở dropdown và reset khi đóng
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

const toggleItem = (item) => {
  const val = item[props.optionValue]
  const index = modelValue.value.indexOf(val)
  
  if (index === -1) {
    modelValue.value = [...modelValue.value, val]
  } else {
    modelValue.value = modelValue.value.filter(id => id !== val)
  }
}

const removeItem = (val) => {
  modelValue.value = modelValue.value.filter(id => id !== val)
}

const isSelected = (item) => {
  return modelValue.value.includes(item[props.optionValue])
}

const handleClickOutside = (event) => {
  if (selectRef.value && !selectRef.value.contains(event.target)) isOpen.value = false
}

const clearAll = (event) => {
  event.stopPropagation()
  if (props.disabled) return
  modelValue.value = []
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div class="relative" ref="selectRef">
    <div
      @click="toggleDropdown"
      :aria-invalid="!!error"
      :aria-disabled="disabled"
      class="dark:bg-dark-900 min-h-11 flex items-center w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-sm text-gray-800 shadow-theme-xs focus-within:border-brand-300 focus-within:ring-3 focus-within:ring-brand-500/10 aria-invalid:border-red-500"
      :class="[disabled ? 'bg-gray-50 cursor-not-allowed opacity-70 select-none' : 'cursor-pointer']"
    >
      <div class="flex flex-wrap gap-2 grow">
        <span v-if="selectedOptions.length === 0" class="text-gray-400 font-normal py-1 px-1">
          {{ placeholder }}
        </span>
        
        <div
          v-for="option in selectedOptions"
          :key="option[optionValue]"
          class="flex items-center gap-1 bg-gray-100 dark:bg-dark-800 text-gray-700 px-2 py-1 rounded-md text-xs font-medium border border-gray-200"
        >
          {{ option[optionLabel] }}
          <button 
            v-if="!disabled"
            type="button" 
            @click.stop="removeItem(option[optionValue])"
            class="text-gray-400 hover:text-red-500 transition-colors"
          >
            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor">
              <path d="M15 5L5 15M5 5L15 15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
        </div>
      </div>
      
      <div class="ml-auto flex items-center gap-2 self-center">
        <button
          v-if="modelValue.length > 0 && !disabled"
          type="button"
          @click.stop="clearAll"
          class="p-0.5 text-gray-400 hover:text-red-500 transition-colors rounded-full hover:bg-gray-100"
        >
           <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M15 5L5 15M5 5L15 15" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </button>

        <svg :class="{ 'rotate-180': isOpen, 'text-gray-300': disabled }" class="transition-transform duration-200 text-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M4.79175 7.39551L10.0001 12.6038L15.2084 7.39551" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </div>

    <transition name="dropdown"> 
      <div v-if="isOpen && !disabled" class="absolute z-30 mt-1 w-full rounded-lg border bg-white shadow-lg overflow-hidden flex flex-col">
        
        <div v-if="searchable" class="p-2 border-b bg-gray-50/50">
          <div class="relative">
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:border-brand-300 focus:ring-2 focus:ring-brand-500/5"
              :placeholder="searchPlaceholder"
              @click.stop
            />
            <svg class="absolute left-2.5 top-2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <ul class="max-h-60 overflow-y-auto divide-y divide-gray-100">
          <li
            v-for="item in filteredOptions"
            :key="item[optionValue]"
            @click="toggleItem(item)"
            class="relative flex items-center w-full px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors"
            :class="{ 'bg-brand-50/50 text-brand-600': isSelected(item) }"
          >
            <span class="grow text-sm" :class="{ 'font-medium': isSelected(item) }">
                {{ item[optionLabel] }}
            </span>
            
            <div class="flex items-center justify-center w-5 h-5 border rounded border-gray-300 mr-2 transition-colors" :class="{ 'bg-brand-500 border-brand-500': isSelected(item) }">
                <svg v-if="isSelected(item)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
          </li>

          <li v-if="filteredOptions.length === 0" class="px-3 py-6 text-center text-sm text-gray-500 italic">
            Không tìm thấy "{{ searchQuery }}"
          </li>
        </ul>
      </div>
    </transition>
    
    <p v-if="error" class="mt-1 text-xs text-red-500 font-medium">{{ error }}</p>
  </div>
</template>