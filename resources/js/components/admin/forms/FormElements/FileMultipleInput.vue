<script setup>
import { ref, watch, onUnmounted} from "vue"
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

const modelValue = defineModel({ default: [] })

const previewUrls = ref([])

watch(modelValue, (newFiles) => {
  previewUrls.value.forEach((url) => URL.revokeObjectURL(url))

  if (newFiles && newFiles.length > 0) {
    previewUrls.value = newFiles.map((file) => URL.createObjectURL(file))
  } else {
    previewUrls.value = []
  }
}, { deep: true, immediate: true })

onUnmounted(() => {
  previewUrls.value.forEach((url) => URL.revokeObjectURL(url))
})

const handleFileChange = (event) => {
  const target = event.target
  const selectedFiles = Array.from(target.files || [])

  const imageFiles = selectedFiles.filter((file) => file.type.startsWith("image/"))

  modelValue.value = [...(modelValue.value || []), ...imageFiles]

  target.value = ""
}

const removeFile = (index) => {
  const currentFiles = [...(modelValue.value || [])]
  currentFiles.splice(index, 1)
  modelValue.value = currentFiles
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
        multiple
        accept="image/*"
        @change="handleFileChange"
        class="focus:border-ring-brand-300 h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 shadow-theme-xs transition-colors 
        file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:pl-3.5 file:pr-3 file:text-sm file:text-gray-700 
        placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden focus:file:ring-brand-300"
      />

      <div v-if="previewUrls.length > 0" class="mt-4 grid grid-cols-3 gap-4 sm:grid-cols-4 lg:grid-cols-5">
        <div 
          v-for="(url, index) in previewUrls" 
          :key="url" 
          class="relative aspect-square group"
        >
          <img 
            :src="url" 
            class="h-full w-full object-cover rounded-lg border border-gray-200 shadow-sm transition-transform duration-200 group-hover:scale-[1.02]"
          />
          <button
            type="button"
            @click="removeFile(index)"
            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 active:scale-90"
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