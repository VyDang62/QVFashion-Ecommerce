<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue'

const props = defineProps({
  options: { type: Array, required: true },
  error: { type: [String, Boolean], default: false },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
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
  <div class="relative" ref="selectRef">
    <div
      @click="toggleDropdown"
      :aria-invalid="!!error"
      :aria-disabled="disabled"
      class="dark:bg-dark-900 h-11 flex items-center w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 aria-invalid:border-red-500"
      :class="[disabled ? 'bg-gray-50 cursor-not-allowed opacity-70' : 'cursor-pointer']"
    >
      <slot name="label" :selected="selectedOption">
        <span v-if="!selectedOption" class="text-gray-400">{{ placeholder }}</span>
        <span v-else class="text-gray-800 font-medium">{{ selectedOption[optionLabel] }}</span>
      </slot>
      
      <div class="ml-auto flex items-center gap-2">
        <button v-if="clearable && selectedOption && !disabled" type="button" @click.stop="clearValue">
           <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M15 5L5 15M5 5L15 15" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </button>
        <svg :class="{ 'rotate-180': isOpen }" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M4.79175 7.39551L10.0001 12.6038L15.2084 7.39551" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
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
            @click="selectItem(item)"
            class="relative flex items-center w-full px-3 py-2.5 cursor-pointer hover:bg-gray-50"
            :class="{ 'bg-brand-50/50 text-brand-600': isSelected(item) }"
          >
            <slot name="option" :option="item">
               <span class="grow">{{ item[optionLabel] }}</span>
            </slot>
            <svg v-if="isSelected(item)" class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </li>

          <li v-if="filteredOptions.length === 0" class="px-3 py-6 text-center text-sm text-gray-500 italic">
            Không tìm thấy kết quả "{{ searchQuery }}"
          </li>
        </ul>
      </div>
    </transition>
    <p v-if="error" class="mt-1 text-xs text-red-500 font-medium">{{ error }}</p>
  </div>
</template>