<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onFinish: () => form.reset('email'),
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Quên mật khẩu" />

        <section id="forgot-password-page" class="bg-gray-50 py-16 min-h-screen flex items-center">
            <div class="container mx-auto px-4">
                
                <div v-if="status" class="max-w-xl mx-auto mb-6 p-4 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg text-center">
                    <i class='fas fa-check-circle mr-2'></i>
                    {{ status }}
                </div>

                <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6 md:p-10">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-semibold mb-3">Quên mật khẩu?</h2>
                        <p class="text-sm text-gray-600">
                            Hãy nhập địa chỉ email của bạn dưới đây và chúng tôi sẽ gửi cho bạn một liên kết để đặt lại mật khẩu mới.
                        </p>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ Email</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                id="email" 
                                class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                :class="form.errors.email ? 'border-red-500' : 'border-gray-300'"
                                placeholder="name@gmail.com"
                                required
                                autofocus
                            >
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary py-3 px-3 rounded-full w-full transition-colors font-semibold shadow-md"
                        >
                            {{ form.processing ? 'Đang gửi yêu cầu...' : 'Gửi liên kết đặt lại mật khẩu' }}
                        </button>

                        <div class="mt-8 text-center border-t pt-6">
                            <p class="text-sm text-gray-600">
                                Bạn đã nhớ mật khẩu? 
                                <Link :href="route('login')" class="text-primary font-semibold hover:underline ml-1">
                                    Quay lại Đăng nhập
                                </Link>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>