<script setup>
import { Head } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';

const props = defineProps({
    page: Object
});
</script>

<template>
    <Head :title="page.meta_title || page.title">
        <meta name="description" :content="page.meta_description">
        <meta name="keywords" :content="page.meta_keywords">
    </Head>

    <CustomerLayout>
        <div class="max-w-4xl mx-auto py-12 px-4">
            <h1 class="text-4xl font-black text-gray-900 mb-8">{{ page.title }}</h1>
            
            <div 
                v-html="page.content" 
                class="prose prose-blue max-w-none text-gray-600 leading-relaxed"
            ></div>
        </div>
    </CustomerLayout>
</template>