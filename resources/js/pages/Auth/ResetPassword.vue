<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Đặt lại mật khẩu" />

        <section id="reset-password-page" class="bg-gray-50 py-16 min-h-screen flex items-center">
            <div class="container mx-auto px-4">
                
                <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6 md:p-10">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-semibold mb-3">Đặt lại mật khẩu</h2>
                        <p class="text-sm text-gray-600">
                            Vui lòng nhập mật khẩu mới cho tài khoản <span class="font-medium text-gray-800">{{ email }}</span>
                        </p>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                id="email" 
                                class="w-full px-4 py-2 border rounded-full bg-gray-50 cursor-not-allowed outline-none border-gray-300 text-gray-500"
                                readonly
                            >
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                            <input 
                                v-model="form.password"
                                type="password" 
                                id="password" 
                                class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                :class="form.errors.password ? 'border-red-500' : 'border-gray-300'"
                                required
                                autofocus
                            >
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="mb-8">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                            <input 
                                v-model="form.password_confirmation"
                                type="password" 
                                id="password_confirmation" 
                                class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary border-gray-300"
                                required
                            >
                            <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary py-3 px-3 rounded-full w-full transition-colors font-semibold shadow-md"
                        >
                            {{ form.processing ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>