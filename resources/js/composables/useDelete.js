import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
export function useDelete() {
    const showDeleteModal = ref(false);
    const itemToDelete = ref(null);
    const isProcessing = ref(false);

    
    const openDeleteModal = (item) => {
        itemToDelete.value = item;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        setTimeout(() => {
            itemToDelete.value = null;
            isProcessing.value = false;
        }, 200);
    };

    const confirmDelete = (routeName) => {
        if (!itemToDelete.value) return;

        isProcessing.value = true;
        router.delete(route(routeName, itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
            onError: () => (isProcessing.value = false),
            onFinish: () => (isProcessing.value = false),
        });
    };

    return {
        showDeleteModal,
        itemToDelete,
        isProcessing,
        openDeleteModal,
        closeDeleteModal,
        confirmDelete,
    };
}