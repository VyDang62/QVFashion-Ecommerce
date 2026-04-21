<script setup lang="ts">
import {cn} from "@/lib/utils"

interface Props{
    processing: boolean
    label?: string
    loadingLabel?: string
    class?: string
}

const props = withDefaults(defineProps<Props>(), {
    label: 'THÊM MỚI',
    loadingLabel: 'ĐANG XỬ LÝ...',
    class: '',
});
</script>

<template>
    <button
        type="submit"
        :disabled="processing"
        :class="cn(
            'w-full py-4 bg-brand-500 text-white rounded-xl font-bold shadow-lg shadow-brand-500/20 transition-all',
            'hover:bg-brand-600 active:scale-[0.98]',
            'disabled:bg-gray-400 disabled:shadow-none disabled:cursor-not-allowed',
            props.class
        )"
    >
        <div class="flex items-center justify-center gap-2">
      <svg 
        v-if="processing" 
        class="animate-spin h-5 w-5 text-white" 
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      
      <span>{{ processing ? loadingLabel : label }}</span>
    </div>
    </button>
</template>