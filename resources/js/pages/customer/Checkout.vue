<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, Link, usePage,useForm, router } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import { useFormatter } from '@/composables/useFormatter';
import axios from 'axios';
import CustomerLocationCheckoutSelect from '@/components/ui/select/CustomerLocationCheckoutSelect.vue';

const { formatPrice } = useFormatter();
const { props } = usePage();

const cartItems = computed(() => props.cartItems || []);
const cartTotal = computed(() => props.cartTotal || 0);
const user = computed(() => props.auth.user || {});

const form = useForm({
    shipping_recipient_name: user.value.full_name || '',
    shipping_phone_number: user.value.phone_number || '',
    shipping_province: user.value.province || '',
    shipping_province_id: user.value.province_id || '',
    shipping_district: user.value.district || '',
    shipping_district_id: user.value.district_id || '',
    shipping_ward: user.value.ward || '',
    shipping_ward_code: user.value.ward_code || null,
    shipping_address_detail: user.value.address_detail || '',
    payment_method: 'cod',
    order_note: '',
    voucher_code: null,
});

const validateClientSide = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.shipping_recipient_name) {
        form.setError('shipping_recipient_name', 'Họ tên người nhận không được để trống!');
        isValid = false;
    }
    if (!form.shipping_phone_number) {
        form.setError('shipping_phone_number', 'Số điện thoại không được để trống!');
        isValid = false;
    }
    if (!form.shipping_province_id) {
        form.setError('province_id', 'Vui lòng chọn Tỉnh / Thành phố!');
        isValid = false;
    }
    if (!form.shipping_district_id) {
        form.setError('district_id', 'Vui lòng chọn Quận / Huyện!');
        isValid = false;
    }
    if (!form.shipping_ward_code) {
        form.setError('ward_code', 'Vui lòng chọn Phường / Xã!');
        isValid = false;
    }
    if (!form.shipping_address_detail) {
        form.setError('shipping_address_detail', 'Vui lòng nhập địa chỉ cụ thể (số nhà, tên đường)!');
        isValid = false;
    }

    return isValid;
}


//Logic Voucher
const voucherInput = ref('');
const appliedVoucher = ref(null);
const status = ref(null);
const statusType = ref('success');

const applyVoucher = async () => {
    if (!voucherInput.value) {
        status.value = "Vui lòng nhập mã giảm giá";
        statusType.value = 'error';
        return;
    }

    try {
        const response = await axios.post(route('cart.applyvoucher'), { 
            code: voucherInput.value,
        });

        if (response.data.success) {
            form.voucher_code = voucherInput;
            appliedVoucher.value = response.data.data;
            status.value = response.data.message;
            statusType.value = 'success';
        }
    } catch (error) {
        appliedVoucher.value = null;
        statusType.value = 'error';
        if (error.response) {
            status.value = error.response?.data?.message || "Mã giảm giá không hợp lệ";
        } else {
            status.value = "Không thể kết nối đến máy chủ";
        }
    }
}


//Địa chỉ và phí ship
const shippingFee = ref(0);
const isLoadingFee = ref(false);

const calculateShippingFee = async () => {
    if (!form.shipping_province_id || !form.shipping_district_id || !form.shipping_ward_code) {
        shippingFee.value = 0;
        return;
    }

    isLoadingFee.value = true;
    try {
        const res = await axios.post('/api/ghn/calculateshippingfee', {
            district_id: form.shipping_district_id,
            ward_code: form.shipping_ward_code,
            total_amount: cartTotal.value,
            total_quantity: cartItems.value.length
        });
        if (res.data.code === 200) {
            shippingFee.value = res.data.data.total;
        }
    } catch (error) {
        console.error("Lỗi tính phí ship:", error);
    } finally {
        isLoadingFee.value = false;
    }
};

watch(() => form.shipping_ward_code, 
    (newVal) => {
        if (newVal) {
            calculateShippingFee();
        }
    }, 
    { immediate: true }
);
//Tổng tiền
const finalTotal = computed(() => {
    const discount = appliedVoucher.value ? appliedVoucher.value.discount_amount : 0;
    return cartTotal.value - discount + shippingFee.value;
});

//Gửi đơn hàng
const submitOrder = () => {
    if(!validateClientSide()){
        const firstError = document.querySelector('.text-red-500');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    form.post(route('checkout.store'), {
        onSuccess: () => {
        }
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Thanh toán" />

        <section id="checkout-page" class="bg-gray-50 py-16">
            <div class="container mx-auto px-4">
                <h1 class="text-2xl font-black mb-8 tracking-tight text-gray-900">Thanh toán</h1>
                
                <div v-if="cartItems.length > 0" class="flex flex-col lg:flex-row gap-8">
                    <div class="lg:w-2/3 space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm p-8">
                            <h2 class="text-md font-bold mb-6 flex items-center gap-2">
                                <i class="fas fa-shipping-fast text-primary"></i>
                                Thông tin giao hàng
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2 md:col-span-1">
                                    <label class="text-xs font-bold text-gray-800">Họ và tên người nhận <span class="text-md text-red-500">*</span></label>
                                    <input v-model="form.shipping_recipient_name" type="text" class="w-full px-4 py-3 mt-2 border border-gray-200 rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Nguyễn Văn A">
                                    <p v-if="form.errors.shipping_recipient_name" class="mt-1.5 ml-4 text-[12px] text-red-500 font-bold">
                                        * {{ form.errors.shipping_recipient_name }}
                                    </p>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="text-xs font-bold text-gray-800">Số điện thoại <span class="text-md text-red-500">*</span></label>
                                    <input v-model="form.shipping_phone_number" type="tel" class="w-full px-4 py-3 mt-2 border border-gray-200 rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="034xxxxxxx">
                                    <p v-if="form.errors.shipping_phone_number" class="mt-1.5 ml-4 text-[12px] text-red-500 font-bold">
                                        * {{ form.errors.shipping_phone_number }}
                                    </p>
                                </div>
                                <div class="col-span-2">
                                    <CustomerLocationCheckoutSelect
                                        v-model:province_id="form.shipping_province_id"
                                        v-model:district_id="form.shipping_district_id"
                                        v-model:ward_code="form.shipping_ward_code"
                                        v-model:province="form.shipping_province"
                                        v-model:district="form.shipping_district"
                                        v-model:ward="form.shipping_ward"
                                        :errors="form.errors"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-xs font-bold text-gray-800">Địa chỉ chi tiết <span class="text-md text-red-500">*</span></label>
                                    <input v-model="form.shipping_address_detail" type="text" class="w-full px-4 py-3 mt-2 border border-gray-200 rounded-full focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Số nhà, tên đường...">
                                    <p v-if="form.errors.shipping_address_detail" class="mt-1.5 ml-4 text-[12px] text-red-500 font-bold">
                                        * {{ form.errors.shipping_address_detail }}
                                    </p>
                                </div>

                                <div class="col-span-2">
                                    <label class="text-xs font-bold text-gray-800">Ghi chú đơn hàng</label>
                                    <textarea v-model="form.order_note" rows="3" class="w-full px-4 py-3 mt-2 border border-gray-200 rounded-full focus:ring-2 focus:ring-primary outline-none" ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm p-8">
                            <h2 class="text-md font-bold mb-6 flex items-center gap-2">
                                <i class="fas fa-credit-card text-primary"></i>
                                Phương thức thanh toán
                            </h2>
                            <div class="space-y-4">
                                <label class="flex items-center p-4 border rounded-xl cursor-pointer transition-all" :class="form.payment_method === 'cod' ? 'border-primary bg-primary/5' : 'border-gray-100'">
                                    <input type="radio" v-model="form.payment_method" value="cod" class="w-4 h-4 text-primary">
                                    <div class="ml-4">
                                        <span class="block font-bold">Thanh toán khi nhận hàng (COD)</span>
                                        <span class="text-xs text-gray-500">Trả tiền mặt khi giao hàng tận nơi</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border rounded-xl cursor-pointer transition-all" :class="form.payment_method === 'banking' ? 'border-primary bg-primary/5' : 'border-gray-100'">
                                    <input type="radio" v-model="form.payment_method" value="banking" class="w-4 h-4 text-primary">
                                    <div class="ml-4">
                                        <span class="block font-bold">Thanh toán qua VNPAY</span>
                                        <span class="text-xs text-gray-500">Chuyển khoản qua QR Code hoặc STK</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="lg:w-1/3">
                        <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
                            <h2 class="text-md font-bold mb-6 pb-2 border-b-2 border-primary w-fit">Đơn hàng của bạn</h2>
                            
                            <div class="max-h-60 overflow-y-auto pt-2 mb-4 space-y-4 pr-2">
                                <div v-for="item in cartItems" :key="item.id" class="flex justify-between items-center gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <img :src="item.image" class="w-12 h-12 rounded-lg object-cover border" alt="">
                                            <span class="absolute -top-2 -right-2 bg-gray-800 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold">{{ item.quantity }}</span>
                                        </div>
                                        <div>
                                            <span class="text-md font-medium truncate">{{ item.name }}</span>
                                            <p class="text-sm font-medium text-gray-700 truncate">{{ item.variant_info }}</p>
                                        </div>
                                    </div>
                                    
                                    <span class="text-sm font-bold">{{ formatPrice(item.subtotal) }}</span>
                                </div>
                            </div>

                            <div class="mb-6 py-4 border-t border-b border-gray-100">
                                <div class="flex items-start gap-2">
                                    <div class="flex flex-col flex-1">
                                        <div class="flex h-10">
                                            <input 
                                                v-model="voucherInput" 
                                                type="text" 
                                                class="flex-1 px-4 border border-gray-200 rounded-l-full text-sm uppercase outline-none focus:border-primary disabled:bg-gray-50 disabled:text-gray-400" 
                                                placeholder="Mã giảm giá" 
                                                @input="voucherInput = voucherInput.toUpperCase()"
                                                :disabled="appliedVoucher"
                                            >
                                            <button 
                                                @click="applyVoucher" 
                                                class="bg-gray-900 text-white px-4 rounded-r-full text-xs font-bold hover:bg-primary transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed whitespace-nowrap" 
                                                :disabled="appliedVoucher"
                                            >
                                                {{ appliedVoucher ? 'ĐÃ DÙNG' : 'ÁP DỤNG' }}
                                            </button>
                                        </div>

                                        <p v-if="status" :class="['text-[10px] mt-2 ml-4 font-medium', statusType === 'success' ? 'text-green-600' : 'text-red-500']">
                                            <i :class="statusType === 'success' ? 'fas fa-check-circle mr-1' : 'fas fa-exclamation-circle mr-1'"></i>
                                            {{ status }}
                                        </p>
                                    </div>

                                    <button 
                                        v-if="appliedVoucher" 
                                        @click="appliedVoucher = null; voucherInput = ''; status = null" 
                                        class="h-10 flex items-center text-[10px] font-bold text-gray-400 hover:text-red-500 underline uppercase transition-colors"
                                    >
                                        Hủy mã
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span>Tạm tính</span>
                                    <span class="font-semibold">{{ formatPrice(cartTotal) }}</span>
                                </div>
                                <div v-if="appliedVoucher" class="flex justify-between">
                                    <span>Giảm giá</span>
                                    <span class="font-semibold">-{{ formatPrice(appliedVoucher.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Phí vận chuyển</span>
                                    <span v-if="isLoadingFee" class="font-semibold text-gray-800">Đang tính...</span>
                                    <span v-else class="font-semibold">{{ shippingFee > 0 ? formatPrice(shippingFee) : 'Chưa tính' }}</span>
                                </div>
                                <div class="pt-4 border-t flex justify-between items-center">
                                    <span class="text-md font-bold text-gray-900">Tổng cộng</span>
                                    <span class="text-md font-bold text-primary">{{ formatPrice(finalTotal) }}</span>
                                </div>
                                
                                <button @click="submitOrder" class="bg-primary text-white border border-primary block w-full hover:bg-transparent hover:text-primary rounded-full py-2 font-bold text-md transition-all duration-300 text-center">
                                    Đặt hàng ngay
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-20 bg-white rounded-3xl shadow-sm">
                    <p class="text-gray-400 mb-6">Không có sản phẩm nào để thanh toán.</p>
                    <Link :href="route('shop.index')" class="bg-primary text-white px-10 py-3 rounded-full font-bold uppercase text-sm">Quay lại cửa hàng</Link>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>