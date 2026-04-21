<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'
import {route} from 'ziggy-js'
import CommonGridShape from '@/components/admin/common/CommonGridShape.vue';
import FullScreenLayout from '@/layouts/admin/FullScreenLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('admin.password.email'), {
            onSuccess: () => form.reset('email'),
    });
};
</script>
<template>
  <Head title="Quên mật khẩu" />

  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 sm:p-0">
      <div class="relative flex flex-col justify-center w-full h-screen lg:flex-row">
        
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
          <div class="w-full max-w-md pt-10 mx-auto">
            <Link
              :href="route('home')"
              class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700"
            >
              <svg class="stroke-current mr-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Về trang chủ
            </Link>
          </div>

          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
            <div>
              <div v-if="status" class="mb-6 p-4 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ status }}
              </div>
              <div class="mb-5 sm:mb-8">
                <h1 class="mb-2 font-semibold text-gray-800 text-title-sm sm:text-title-md">Quên mật khẩu</h1>
                <p class="text-sm text-gray-500">Vui lòng điền email liên kết với tài khoản, chúng tôi sẽ gửi đường dẫn liên kết để đặt lại mật khẩu!</p>
              </div>
              
              <div>
                <form @submit.prevent="submit">
                  <div class="space-y-5">
                    <div>
                      <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Email<span class="text-error-500 text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.email"
                        type="email"
                        id="email"
                        placeholder="qvfashion@gmail.com"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10"
                        :class="form.errors.email ? 'border-red-500' : 'border-gray-300'"
                      />
                      <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>
                    <div>
                      <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-50"
                      >
                        {{ form.processing ? 'Đang gửi...' : 'Gửi liên kết đặt lại mật khẩu' }}
                      </button>
                    </div>
                    <div class="flex items-center justify-between">
                      <Link :href="route('admin.login')" class="text-sm text-brand-500 hover:text-brand-600">
                        Về trang đăng nhập
                      </Link>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="relative items-center hidden w-full h-full lg:w-1/2 bg-brand-950 lg:grid">
          <div class="flex items-center justify-center z-1">
            <CommonGridShape />
            <div class="flex flex-col items-center max-w-xs text-center">
              <Link :href="route('home')" class="block mb-4">
                <img width="231" height="48" :src="$page.props.settings?.logo || '/images/logo/auth-logo.svg'" alt="Logo" />
              </Link>
              <p class="text-gray-400">
                Hệ thống quản lý Cửa hàng thời trang - Quản trị nội bộ dành cho nhân viên.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>