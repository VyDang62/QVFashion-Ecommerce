<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="404 - Không tìm thấy trang" />

    <section id="404-page" class="bg-white py-16 flex items-center justify-center min-h-screen">
        <div class="mx-auto max-w-screen-lg px-4 md:px-8">
            <div class="grid gap-8 sm:grid-cols-2">
                <div class="flex flex-col items-center justify-center sm:items-start md:py-24 lg:py-32">
                    <h1 class="text-4xl font-black text-primary mb-5 uppercase tracking-tighter">
                        404 - Page Not Found
                    </h1>
                    <p class="text-gray-500 mb-8 text-center sm:text-left">
                        Trang bạn đang tìm kiếm có thể đã bị xóa, đổi tên hoặc tạm thời không khả dụng. 
                        Vui lòng kiểm tra lại đường dẫn.
                    </p>
                    
                    <Link 
                        href="/" 
                        class="bg-primary hover:bg-white border border-primary text-white hover:text-primary font-bold px-8 py-3 rounded-full inline-block transition-all duration-300 shadow-lg shadow-blue-100"
                    >
                        VỀ TRANG CHỦ
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>