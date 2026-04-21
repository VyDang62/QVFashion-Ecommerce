<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});

const cleanLabel = (label) => {
    if (label.includes('Previous')) {
        return '<i class="fas fa-chevron-left text-lg"></i>'; 
    }
    if (label.includes('Next')) {
        return '<i class="fas fa-chevron-right text-lg"></i>';
    }
    return label;
};
</script>

<template>
    <div v-if="links.length > 3" class="flex justify-center mt-12 mb-8">
        <nav aria-label="Page navigation">
            <ul class="inline-flex items-center space-x-3">
                <li v-for="(link, key) in links" :key="key">
                    <div v-if="link.url === null"
                         class="w-12 h-12 flex items-center justify-center rounded-full text-gray-200 border border-gray-100 cursor-not-allowed"
                         v-html="cleanLabel(link.label)" 
                    />

                    <Link v-else
                          :href="link.url"
                          class="w-12 h-12 flex items-center justify-center rounded-full transition-all duration-300 font-bold"
                          :class="[
                              link.active 
                              ? 'bg-primary text-white shadow-xl' 
                              : 'text-gray-500 hover:bg-primary hover:text-white border border-transparent'
                          ]"
                          preserve-scroll
                          v-html="cleanLabel(link.label)" 
                    />
                </li>
            </ul>
        </nav>
    </div>
</template>

