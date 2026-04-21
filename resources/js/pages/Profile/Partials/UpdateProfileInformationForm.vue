<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import CustomerLocationSelect from '@/components/ui/select/CustomerLocationSelect.vue';

const user = usePage().props.auth.user;

const form = useForm({
    full_name: user.full_name || '',
    email: user.email || '',
    phone_number: user.phone_number || '',
    province: user.province || '',
    province_id: user.province_id || '',
    district: user.district || '',
    district_id: user.district_id || '',
    ward: user.ward || '',
    ward_code: user.ward_code || null,
    address_detail: user.address_detail || '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-semibold text-gray-900">Thông tin cá nhân</h2>
            <p class="mt-1 text-sm text-gray-600">
                Cập nhật thông tin cơ bản và địa chỉ nhận hàng của bạn.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Họ và tên</label>
                    <input 
                        v-model="form.full_name" 
                        type="text"
                        class="w-full px-4 py-2.5 border rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                        :class="form.errors.full_name ? 'border-red-500' : 'border-gray-300'"
                        required
                    />
                    <p v-if="form.errors.full_name" class="mt-1.5 text-xs text-red-500 ml-1">{{ form.errors.full_name }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Email</label>
                    <input 
                        v-model="form.email" 
                        type="email"
                        class="w-full px-4 py-2.5 border rounded-full bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 outline-none"
                        disabled
                    />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Số điện thoại</label>
                    <input 
                        v-model="form.phone_number" 
                        type="tel"
                        class="w-full px-4 py-2.5 border rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all border-gray-300"
                        placeholder="Nhập số điện thoại..."
                    />
                    <p v-if="form.errors.phone_number" class="mt-1.5 text-xs text-red-500 ml-1">{{ form.errors.phone_number }}</p>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Địa chỉ nhận hàng</h3>
                    
                    <div class="flex flex-col gap-6">
                        <CustomerLocationSelect 
                            v-model:province_id="form.province_id"
                            v-model:district_id="form.district_id"
                            v-model:ward_code="form.ward_code"
                            v-model:province="form.province"      
                            v-model:district="form.district" 
                            v-model:ward="form.ward"       
                            :errors="form.errors"
                        />

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Địa chỉ chi tiết (Số nhà, tên đường)</label>
                            <input 
                                v-model="form.address_detail" 
                                type="text"
                                class="w-full px-4 py-2.5 border rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                                :class="form.errors.address_detail ? 'border-red-500' : 'border-gray-300'"
                                placeholder="Ví dụ: 123 Nguyễn Huệ"
                            />
                            <p v-if="form.errors.address_detail" class="mt-1.5 text-xs text-red-500 ml-1">{{ form.errors.address_detail }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 border-t pt-6">
                <button 
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary py-3 px-10 rounded-full transition-all font-semibold shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Đang lưu...' : 'Lưu thông tin' }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-medium flex items-center gap-1">
                        Đã cập nhật thành công!
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>