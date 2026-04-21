import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { PageProps } from '@/types';

export function useFlash() {
    const page = usePage<PageProps>();
    const flashId = ref(0);
    watch(() => page.props.flash, () => {
        flashId.value++; 
    }, { deep: true });

    const flashSuccess = computed(() => page.props.flash?.success);
    const flashError = computed(() => page.props.flash?.error);
    const flashWarning = computed(() => page.props.flash?.warning);
    const flashInfo = computed(() => page.props.flash?.info);

    const hasFlash = computed(() => 
        !!(flashSuccess.value || flashError.value || flashWarning.value || flashInfo.value)
    );

    return {
        flashSuccess,
        flashError,
        flashWarning,
        flashInfo,
        hasFlash,
        flashId // Trả về flashId để component lắng nghe
    };
}