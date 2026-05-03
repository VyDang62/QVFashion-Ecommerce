<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import { useFormatter } from '@/composables/useFormatter';
import axios from 'axios';

const { formatPrice } = useFormatter();
const page = usePage();
const cartItems = computed(() => page.props.cartItems || []);
const cartTotal = computed(() => page.props.cartTotal || 0);

const updateQuantity = (id, currentQuantity, amount) => {
    const newQuantity = currentQuantity + amount;
    if (newQuantity < 1) return;

    router.patch(route('cart.update', id), {
        quantity: newQuantity
    }, { preserveScroll: true });
};

const voucherInput = ref('');
const appliedVoucher = ref(null);
const status = ref(null);
const statusType = ref('success');
const isProcessing = ref(false);

const applyVoucher = async () => {
    if (!page.props.auth.user) {
        router.visit(route('login'));
        return;
    }

    if (!voucherInput.value) {
        status.value = "Vui lòng nhập mã giảm giá";
        statusType.value = 'error';
        return;
    }

    isProcessing.value = true;
    status.value = null;

    try {
        const response = await axios.post(route('cart.applyvoucher'), { 
            code: voucherInput.value,
        });

        if (response.data.success) {
            appliedVoucher.value = response.data.data;
            status.value = response.data.message;
            statusType.value = 'success';
        }
    } catch (error) {
        appliedVoucher.value = null;
        statusType.value = 'error';
        status.value = error.response?.data?.message || "Mã giảm giá không hợp lệ";
    } finally {
        isProcessing.value = false;
    }
}

const finalTotal = computed(() => {
    if (appliedVoucher.value) {
        return appliedVoucher.value.new_total;
    }
    return cartTotal.value;
});

const removeItem = (id) => {
    router.delete(route('cart.destroy', id), { preserveScroll: true });
};

const clearCart = () => {
    router.delete(route('cart.clear'), {}, { preserveScroll: true });
};
</script>
<template>
    <CustomerLayout>
        <Head title="Giỏ hàng của bạn" />

        <section id="cart-page" class="bg-gray-50 py-16 min-h-screen">
            <div class="container mx-auto px-4">
                <h1 class="text-2xl font-black mb-8 tracking-tight text-gray-900">Giỏ hàng</h1>
                <div v-if="cartItems.length === 0" class="bg-white rounded-2xl shadow-sm p-12 text-center">
                    <div class="mb-6">
                        <i class="fas fa-shopping-basket text-gray-200 text-8xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Giỏ hàng của bạn đang trống</h2>
                    <p class="text-gray-500 mb-8">Hãy lấp đầy nó bằng những món đồ thời trang mới nhất!</p>
                    <Link :href="route('shop.index')" class="bg-primary text-white px-8 py-3 rounded-full font-bold hover:shadow-lg transition-all inline-block">
                        Mua sắm ngay
                    </Link>
                </div>

                <div v-else class="flex flex-col lg:flex-row gap-8">
                    <div class="lg:w-3/4">
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 border-b border-gray-100">
                                            <th class="p-6 font-bold text-sm">Sản phẩm</th>
                                            <th class="p-6 font-bold text-sm text-center">Giá</th>
                                            <th class="p-6 font-bold text-sm text-center">Số lượng</th>
                                            <th class="p-6 font-bold text-sm text-right">Tổng</th>
                                            <th class="p-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="item in cartItems" :key="item.id" class="group">
                                            <td class="p-6">
                                                <div class="flex items-center gap-4">
                                                    <div class="relative h-20 w-20 flex-shrink-0">
                                                        <img :src="item.image" 
                                                            class="h-full w-full rounded-lg object-cover border border-gray-100 shadow-sm" 
                                                            alt="Product image">
                                                        <span v-if="item.is_flash_sale" 
                                                            class="absolute top-0 left-0 bg-red-600 text-white text-[8px] font-black px-2 py-0.5 rounded-tl-lg rounded-br-lg shadow-sm uppercase tracking-tighter">
                                                            SALE
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 uppercase text-sm mb-1">{{ item.name }}</h4>
                                                        <p class="text-xs text-gray-800 italic">{{ item.variant_info }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-6 text-center">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-gray-900">{{ formatPrice(item.current_price) }}</span>
                                                    <span v-if="item.is_flash_sale" class="text-xs text-gray-400 line-through">
                                                        {{ formatPrice(item.price) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="p-6">
                                                <div class="flex items-center justify-center gap-3">
                                                    <button @click="updateQuantity(item.id, item.quantity, -1)" 
                                                            class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors disabled:opacity-30"
                                                            :disabled="item.quantity <= 1">
                                                        <i class="fas fa-minus text-[10px]"></i>
                                                    </button>
                                                    <span class="font-bold w-4 text-center">{{ item.quantity }}</span>
                                                    <button @click="updateQuantity(item.id, item.quantity, 1)"
                                                            class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
                                                        <i class="fas fa-plus text-[10px]"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-6 text-right font-bold text-primary">
                                                {{ formatPrice(item.subtotal) }}
                                            </td>
                                            <td class="p-6 text-center">
                                                <button @click="removeItem(item.id)" class="text-gray-300 hover:text-red-500 transition-colors">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-6 bg-gray-50/50 flex flex-col lg:flex-row justify-between items-center gap-4">
                                <div class="flex items-start w-full md:w-auto">
                                    <div class="flex flex-col">
                                        <div class="flex h-11 w-full lg:w-auto">
                                            <input 
                                                v-model="voucherInput"
                                                type="text" 
                                                placeholder="Mã giảm giá" 
                                                class="border border-gray-300 rounded-l-full px-6 focus:outline-none focus:border-primary w-full lg:w-64 transition-colors uppercase h-full text-sm"
                                                @input="voucherInput = voucherInput.toUpperCase()"
                                                @keyup.enter.prevent="applyVoucher"
                                                :disabled="appliedVoucher"
                                            >
                                            <button 
                                                @click="applyVoucher" 
                                                class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary rounded-r-full px-6 font-bold text-sm transition-all duration-300 h-full flex items-center whitespace-nowrap"
                                                :class="{'opacity-50 cursor-not-allowed': appliedVoucher}"
                                            >
                                                {{ appliedVoucher ? 'Đã áp dụng' : 'Áp dụng' }}
                                            </button>
                                        </div>

                                        <p v-if="status" 
                                            :class="['text-[10px] mt-2 ml-4 font-medium', statusType === 'success' ? 'text-green-600' : 'text-red-500']"
                                        >
                                            <i :class="statusType === 'success' ? 'fas fa-check-circle mr-1' : 'fas fa-exclamation-circle mr-1'"></i>
                                            {{ status }}
                                        </p>
                                    </div>

                                    <button 
                                        v-if="appliedVoucher" 
                                        @click="appliedVoucher = null; voucherInput = ''; status = null" 
                                        class="h-11 flex items-center ml-4 text-sm text-gray-500 hover:text-red-500 underline whitespace-nowrap transition-colors"
                                    >
                                        Hủy mã
                                    </button>
                                </div>

                                <div class="flex flex-wrap justify-end gap-3 w-full lg:w-auto h-11">
                                    <button 
                                        @click="clearCart" 
                                        class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary rounded-full px-8 font-bold text-sm transition-all duration-300 h-full flex items-center"
                                    >
                                        Xóa giỏ hàng
                                    </button>
                                    <Link 
                                        :href="route('shop.index')" 
                                        class="bg-primary text-white border border-primary hover:bg-transparent hover:text-primary rounded-full px-8 font-bold text-sm transition-all duration-300 text-center h-full flex items-center justify-center"
                                    >
                                        Tiếp tục mua sắm
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:w-1/4">
                        <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
                            <h2 class="text-md font-bold mb-6 pb-2 border-b-2 border-primary w-fit">Tổng đơn hàng</h2>
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span>Tạm tính</span>
                                    <span class="font-semibold">{{ formatPrice(cartTotal) }}</span>
                                </div>
                                <div v-if="appliedVoucher" class="flex justify-between">
                                    <span>Voucher ({{ appliedVoucher?.code }})</span>
                                    <span>-{{ formatPrice(appliedVoucher?.discount_amount) }}</span>
                                </div>
                                <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                    <span class="text-md font-bold text-gray-900">Tổng cộng</span>
                                    <span class="text-md font-bold text-primary">{{ formatPrice(finalTotal) }}</span>
                                </div>
                                <Link :href="route('checkout')" class="bg-primary text-white border border-primary block w-full hover:bg-transparent hover:text-primary rounded-full py-2 font-bold text-md transition-all duration-300 text-center">
                                    Thanh toán ngay
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>