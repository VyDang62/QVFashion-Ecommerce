<script setup>
import { ref, watch, onUnmounted } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps({
  label: {
    type: String,
    default: ""
  },
  error: {
    type: [String, Boolean],
    default: false
  },
  class: {
    type: String,
    default: ""
  }
})

const modelValue = defineModel({ default: null })

const previewUrl = ref(null)

watch(modelValue, (newFile) => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }

  if (newFile instanceof File) {
    previewUrl.value = URL.createObjectURL(newFile)
  } else {
    previewUrl.value = null
  }
}, { immediate: true })

onUnmounted(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})

const handleFileChange = (event) => {
  const file = event.target.files?.[0] || null
  
  modelValue.value = file
}

const removeFile = () => {
  modelValue.value = null
}
</script>

<template>
  <div :class="cn('space-y-2', props.class)">
    <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700">
      {{ label }}
    </label>
    
    <div class="relative">
      <input
        type="file"
        accept="image/*"
        @change="handleFileChange"
        class="focus:border-ring-brand-300 h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 shadow-theme-xs transition-colors file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:pl-3.5 file:pr-3 file:text-sm file:text-gray-700 placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden focus:file:ring-brand-300"
      />

      <div v-if="previewUrl" class="mt-4">
        <div class="relative aspect-square w-32 group">
          <img 
            :src="previewUrl" 
            class="h-full w-full object-cover rounded-lg border border-gray-200 shadow-sm transition-all group-hover:brightness-90"
          />
          <button
            type="button"
            @click="removeFile"
            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M18 6L6 18M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <p v-if="error" class="text-xs text-red-500 mt-1 font-medium">{{ error }}</p>
  </div>
</template>