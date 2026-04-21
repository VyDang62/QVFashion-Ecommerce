<script setup lang="ts">
import { useFlash } from '@/composables/useFlash';
import Alert from '../admin/ui/Alert.vue';
import { ref, watch, nextTick } from 'vue';

const { flashSuccess, flashError, flashWarning, flashInfo, flashId } = useFlash();

const isVisible = ref(false);
const currentMessage = ref('');
const currentType = ref<'success' | 'error' | 'warning' | 'info'>('success');
const timeoutId = ref<ReturnType<typeof setTimeout> | null>(null);

const startTimer = (msg: string, type: any) => {
    isVisible.value = false;
    
    if (timeoutId.value) clearTimeout(timeoutId.value);
    nextTick(() => {
        currentMessage.value = msg;
        currentType.value = type;
        isVisible.value = true;

        timeoutId.value = setTimeout(() => {
            isVisible.value = false;
        }, 5000);
    });
}
watch(() => flashId.value, () => {
    if (flashSuccess.value) startTimer(flashSuccess.value, 'success');
    else if (flashError.value) startTimer(flashError.value, 'error');
    else if (flashWarning.value) startTimer(flashWarning.value, 'warning');
    else if (flashInfo.value) startTimer(flashInfo.value, 'info');
},{ immediate: true }
);
</script>

<template>
  <div class="fixed top-20 right-5 z-[100] w-full max-w-sm space-y-3 pointer-events-none">
    <TransitionGroup 
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-x-10 opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition ease-in duration-200 absolute w-full"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <Alert 
        v-if="isVisible && currentMessage" 
        :key="currentMessage" 
        :variant="currentType" 
        :title="currentType === 'success' ? 'Thành công' : 'Lỗi'" 
        :message="currentMessage" 
        @close="isVisible = false"
        class="pointer-events-auto shadow-2xl border-l-4"
        :class="currentType === 'success' ? 'border-green-500' : 'border-red-500'"
      />
    </TransitionGroup>
  </div>
</template>