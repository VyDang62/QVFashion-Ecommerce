<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    active: {
        type: String,
        default: 'profile'
    }
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50">
            <h3 class="font-bold text-gray-800">Quản lý tài khoản</h3>
        </div>
        <nav class="p-2 space-y-1">
            <Link 
                :href="route('profile.edit')" 
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors"
                :class="active === 'profile' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                Thông tin tài khoản
            </Link>
            <Link 
                :href="route('wishlist')"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors"
                :class="active === 'wishlist' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                Sản phẩm yêu thích
            </Link>
            <Link 
                :href="route('orderhistory')"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors"
                :class="active === 'orders' ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-50'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                Đơn hàng của tôi
            </Link>
            <Link 
                :href="route('logout')" 
                method="post" 
                as="button" 
                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Đăng xuất
            </Link>
        </nav>
    </div>
</template>