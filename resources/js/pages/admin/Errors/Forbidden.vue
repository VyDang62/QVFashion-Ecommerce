<template>
  <Head title="403"/>
  <div
    class="relative flex flex-col items-center justify-center min-h-screen p-6 overflow-hidden z-1"
  >
    <common-grid-shape />
    <!-- Centered Content -->
    <div class="mx-auto w-full max-w-[242px] text-center sm:max-w-[472px]">
      <h1 class="mb-8 font-bold text-gray-800 text-title-md xl:text-title-2xl">
        ERROR
      </h1>
      <img src="/admin/images/error/403.svg" alt="403" class="dark:hidden" />
      <p class="mt-10 mb-6 text-base text-gray-700 sm:text-lg">
        Bạn không có quyền truy cập vào trang này!
      </p>
        <Link
          :href="route('admin.home')"
          class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800"
        >
          Trở về bảng điều khiển
        </Link>
    </div>
  </div>
</template>

<script setup>
import CommonGridShape from '@/components/admin/common/CommonGridShape.vue';
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
</script>
