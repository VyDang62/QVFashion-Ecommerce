<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

const registerForm = useForm({
    full_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password','password_confirmation'),
    });
};

</script>

<template>
    <CustomerLayout>
        <Head title="Đăng nhập & Đăng ký" />

        <section id="register-login-page" class="bg-gray-50 py-16 min-h-screen flex items-center">
            <div class="container mx-auto px-4">
                
                <div v-if="status" class="max-w-4xl mx-auto mb-6 p-4 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg">
                    {{ status }}
                </div>

                <div class="flex flex-col md:flex-row gap-4 max-w-6xl mx-auto">
                    
                    <div class="md:w-1/2 bg-white rounded-lg shadow-md p-4 md:p-10">
                        <h2 class="text-2xl font-semibold mb-6">Đăng nhập</h2>
                        <form @submit.prevent="submitLogin">
                            <div class="mb-4">
                                <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input 
                                    v-model="loginForm.email"
                                    type="email" 
                                    id="login-email" 
                                    class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="loginForm.errors.email ? 'border-red-500' : 'border-gray-300'"
                                    required
                                >
                                <p v-if="loginForm.errors.email" class="mt-1 text-xs text-red-500">{{ loginForm.errors.email }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                                <input 
                                    v-model="loginForm.password"
                                    type="password" 
                                    id="login-password" 
                                    class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="loginForm.errors.password ? 'border-red-500' : 'border-gray-300'"
                                    required
                                >
                                <p v-if="loginForm.errors.password" class="mt-1 text-xs text-red-500">{{ loginForm.errors.password }}</p>
                            </div>

                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <input v-model="loginForm.remember" type="checkbox" id="remember-me" class="mr-2 rounded text-primary focus:ring-primary">
                                    <label for="remember-me" class="text-sm text-gray-600">Ghi nhớ đăng nhập</label>
                                </div>
                                <Link :href="route('password.request')" class="text-sm text-primary hover:underline">
                                    Quên mật khẩu?
                                </Link>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="loginForm.processing"
                                class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary py-2.5 px-3 rounded-full w-full transition-colors font-semibold"
                            >
                                {{ loginForm.processing ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                            </button>
                        </form>
                    </div>

                    <div class="md:w-1/2 bg-white rounded-lg shadow-md p-4 md:p-10">
                        <h2 class="text-2xl font-semibold mb-6">Đăng ký</h2>
                        <form @submit.prevent="submitRegister">
                            <div class="mb-4">
                                <label for="register-name" class="block text-sm font-medium text-gray-700 mb-1">Họ tên</label>
                                <input 
                                    v-model="registerForm.full_name"
                                    type="text" 
                                    id="register-name" 
                                    class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="registerForm.errors.full_name ? 'border-red-500' : 'border-gray-300'"
                                    required
                                >
                                <p v-if="registerForm.errors.full_name" class="mt-1 text-xs text-red-500">{{ registerForm.errors.name }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="register-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input 
                                    v-model="registerForm.email"
                                    type="email" 
                                    id="register-email" 
                                    class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="registerForm.errors.email ? 'border-red-500' : 'border-gray-300'"
                                    required
                                >
                                <p v-if="registerForm.errors.email" class="mt-1 text-xs text-red-500">{{ registerForm.errors.email }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                                <input 
                                    v-model="registerForm.password"
                                    type="password" 
                                    id="register-password" 
                                    class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="registerForm.errors.password ? 'border-red-500' : 'border-gray-300'"
                                    required
                                >
                                <p v-if="registerForm.errors.password" class="mt-1 text-xs text-red-500">{{ registerForm.errors.password }}</p>
                            </div>

                            <div class="mb-6">
                                <label for="register-confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
                                <input 
                                    v-model="registerForm.password_confirmation"
                                    type="password" 
                                    id="register-confirm-password" 
                                    class="w-full px-4 py-2 border rounded-full focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary border-gray-300"
                                    required
                                >
                            </div>

                            <button 
                                type="submit" 
                                :disabled="registerForm.processing"
                                class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary py-2.5 px-3 rounded-full w-full transition-colors font-semibold"
                            >
                                {{ registerForm.processing ? 'Đang đăng ký...' : 'Đăng ký' }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </section>
    </CustomerLayout>
</template>