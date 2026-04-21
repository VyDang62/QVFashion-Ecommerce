<script setup>
import { ref } from 'vue';
import ConfirmNotification from '@/components/common/ConfirmNotification.vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    message: {
        type: String,
        required: true
    },
    confirmText: {
        type: String,
        default: 'Xác nhận'
    },
    loading: {
        type: Boolean,
        default: false
    },
    variant: {
        type: String,
        default: 'danger' // 'danger' | 'primary' | 'success'
    }
});

const emit = defineEmits(['confirm']);

const showModal = ref(false);

const handleConfirm = () => {
    emit('confirm');
    if (!props.loading) {
        showModal.value = false;
    }
};
</script>

<template>
    <div class="inline-block">
        <div @click.stop="showModal = true" class="cursor-pointer">
            <slot name="trigger"></slot>
        </div>

        <ConfirmNotification
            :show="showModal"
            :title="title"
            :message="message"
            :loading="loading"
            :confirm-text="confirmText"
            :variant="variant" 
            @close="showModal = false"
            @confirm="handleConfirm"
        />
    </div>
</template>