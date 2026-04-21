<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-semibold text-gray-900">Đổi mật khẩu</h2>
            <p class="mt-1 text-sm text-gray-600">
                Mật khẩu cần chứa ít nhất 8 ký tự.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Mật khẩu hiện tại</label>
                    <input 
                        ref="currentPasswordInput"
                        v-model="form.current_password" 
                        type="password"
                        class="w-full px-4 py-2.5 border rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                        :class="form.errors.current_password ? 'border-red-500' : 'border-gray-300'"
                        autocomplete="current-password"
                    />
                    <p v-if="form.errors.current_password" class="mt-1.5 text-xs text-red-500 ml-1">{{ form.errors.current_password }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Mật khẩu mới</label>
                    <input 
                        ref="passwordInput"
                        v-model="form.password" 
                        type="password"
                        class="w-full px-4 py-2.5 border rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                        :class="form.errors.password ? 'border-red-500' : 'border-gray-300'"
                        autocomplete="new-password"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500 ml-1">{{ form.errors.password }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Xác nhận mật khẩu mới</label>
                    <input 
                        v-model="form.password_confirmation" 
                        type="password"
                        class="w-full px-4 py-2.5 border rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all border-gray-300"
                        autocomplete="new-password"
                    />
                    <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-red-500 ml-1">{{ form.errors.password_confirmation }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4 border-t pt-6">
                <button 
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary py-3 px-10 rounded-full transition-all font-semibold shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-medium flex items-center gap-1">
                        Đã đổi mật khẩu thành công!
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>