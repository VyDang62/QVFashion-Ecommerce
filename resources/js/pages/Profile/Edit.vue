<script setup>
import { Head } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UserCenterSidebar from '@/layouts/customer/UserCenterSidebar.vue';
defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Thông tin tài khoản" />

    <CustomerLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div class="flex flex-col md:flex-row gap-6">
                    
                    <aside class="w-full md:w-1/4">
                        <UserCenterSidebar active="profile" />
                    </aside>

                    <main class="w-full md:w-3/4 space-y-6">
                        
                        <div class="bg-white p-4 shadow-sm border border-gray-100 sm:rounded-lg sm:p-8">
                            <UpdateProfileInformationForm
                                class="max-w"
                            />
                        </div>

                        <div class="bg-white p-4 shadow-sm border border-gray-100 sm:rounded-lg sm:p-8">
                            <UpdatePasswordForm class="max-w-xl" />
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>