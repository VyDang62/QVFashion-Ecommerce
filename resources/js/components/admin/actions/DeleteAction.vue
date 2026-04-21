<script setup>
import ConfirmNotification from '@/components/common/ConfirmNotification.vue';
import {useDelete} from '@/composables/useDelete'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    routeName: {
        type: String,
        required: true
    },
    displayName: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: 'Xác nhận xóa'
    },
    message:{
        type: String,
        default: 'Bạn có chắc chắn muốn xóa? Hành động này không thể hoàn tác!'
    }
});
const { 
    showDeleteModal, 
    isProcessing, 
    openDeleteModal, 
    closeDeleteModal, 
    confirmDelete 
} = useDelete();

const handleConfirm = () => {
    confirmDelete(props.routeName);
};
</script>

<template>
    <button 
        @click="openDeleteModal(item)" 
        type="button"
        class="text-gray-400 hover:text-red-600 transition-colors"
        title="Xóa"
    >
        <slot>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="1.5"/>
            </svg>
        </slot>
    </button>

    <ConfirmNotification
        :show="showDeleteModal"
        :title="title"
        :message="message"
        :loading="isProcessing"
        @close="closeDeleteModal"
        @confirm="handleConfirm"
    />
</template>