<template>
  <div>
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl lg:p-6 bg-white">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-col items-center w-full gap-6 xl:flex-row">
          <div class="order-3 xl:order-2">
            <h4 class="mb-2 text-lg font-semibold text-center text-gray-800 xl:text-left">
              {{ user.full_name }}
            </h4>
            <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
              <p class="text-sm text-gray-500">{{ user.role_name || 'Quản trị viên' }}</p>
              <div class="hidden h-3.5 w-px bg-gray-300 xl:block"></div>
              <p class="text-sm text-gray-500">{{ user.city || 'Chưa cập nhật địa chỉ' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePage} from '@inertiajs/vue3';
const user = usePage().props.auth.user;
</script>