<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/admin/common/ComponentCard.vue';
import { useFormatter } from '@/composables/useFormatter';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    order: Object,
});

const { formatPrice, formatDateTime } = useFormatter();
const isProcessing = ref(false);
const canApproveOrCancel = computed(() => [1, 3].includes(props.order.order_status));
const canSetShipped = computed(() => props.order.order_status === 4);
const canComplete = computed(() => props.order.order_status === 5);
const canApproveReturn = computed(() => props.order.order_status === 7);
const canConfirmReturned = computed(() => props.order.order_status === 8);
const canRefund = computed(() => props.order.order_status === 9);
const updateStatus = (newStatus) => {
    router.patch(route('admin.orders.update', props.order.id), {
        order_status: newStatus
    }, {
        onStart: () => isProcessing.value = true,
        onFinish: () => isProcessing.value = false,
        preserveScroll: true
    });
};
</script>

<template>
    <Head :title="'Đơn hàng: ' + order.order_code" />
    <AdminLayout>
        <PageBreadcrumb :pageTitle="'Chi tiết đơn: ' + order.order_code" parentName="Quản lý đơn hàng" :parentRoute="route('admin.orders.index')" />

        <div class="flex flex-col gap-4 mb-6">
            <div :class="[
                'p-4 rounded-xl border flex justify-between items-center shadow-sm', order.status_info.bg, order.status_info.class
            ]">
                <div class="flex items-center gap-4">
                    <div>
                        <span class="text-xs uppercase font-bold block">Trạng thái đơn hàng</span>
                        <span class="font-black text-lg">
                            {{ order.status_info.label }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <template v-if="canApproveOrCancel">
                        <ConfirmAction
                            v-if="can('orders.cancel')"
                            title="Hủy đơn hàng"
                            message="Hành động này sẽ HỦY đơn và HOÀN KHO tự động. Bạn chắc chắn chứ?"
                            confirmText="Xác nhận hủy"
                            :loading="isProcessing"
                            @confirm="updateStatus(0)"
                        >
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-white border border-red-200 text-red-600 text-sm font-bold rounded-xl hover:bg-red-50 transition-all active:scale-95 shadow-sm">
                                    HỦY ĐƠN
                                </button>
                            </template>
                        </ConfirmAction>

                        <ConfirmAction
                            v-if="can('orders.approve')"
                            title="Duyệt đơn hàng"
                            message="Xác nhận duyệt đơn hàng này và chuyển sang trạng thái Đang giao hàng?"
                            confirmText="Duyệt đơn"
                            :loading="isProcessing"
                            @confirm="updateStatus(4)"
                        >
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                                    DUYỆT ĐƠN HÀNG
                                </button>
                            </template>
                        </ConfirmAction>
                    </template>
                    <template v-else-if="canSetShipped && can('orders.approve')">
                        <ConfirmAction
                            title="Xác nhận đã giao"
                            message="Xác nhận Shipper đã giao hàng thành công đến tay khách hàng?"
                            confirmText="Đã giao hàng"
                            :loading="isProcessing"
                            @confirm="updateStatus(5)"
                        >
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-purple-600 text-white text-sm font-bold rounded-xl hover:bg-purple-700 shadow-lg shadow-purple-500/20 transition-all active:scale-95">
                                    XÁC NHẬN ĐÃ GIAO
                                </button>
                            </template>
                        </ConfirmAction>
                    </template>

                    <template v-else-if="canComplete && can('orders.approve')">
                        <ConfirmAction
                            title="Xác nhận hoàn thành"
                            message="Bạn có chắc chắn đơn hàng này đã được giao thành công?"
                            confirmText="Hoàn thành đơn"
                            :loading="isProcessing"
                            @confirm="updateStatus(6)"
                        >
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 shadow-lg shadow-green-500/20 transition-all active:scale-95 flex items-center gap-2">
                                    HOÀN THÀNH ĐƠN
                                </button>
                            </template>
                        </ConfirmAction>
                    </template>
                    <template v-else-if="canApproveReturn && can('orders.approve')">
                        <ConfirmAction title="Duyệt yêu cầu trả hàng" message="Hệ thống sẽ ghi nhận và chuyển trạng thái sang 'Đang thu hồi hàng'. Tiếp tục?" confirmText="Duyệt yêu cầu" :loading="isProcessing" @confirm="updateStatus(8)">
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-rose-600 text-white text-sm font-bold rounded-xl hover:bg-rose-700 shadow-lg shadow-rose-500/20 transition-all uppercase">Duyệt Trả Hàng</button>
                            </template>
                        </ConfirmAction>
                    </template>

                    <template v-else-if="canConfirmReturned && can('orders.approve')">
                        <ConfirmAction title="Xác nhận đã nhận hàng" message="Xác nhận hàng hóa đã về kho an toàn để chuẩn bị hoàn tiền?" confirmText="Đã nhận hàng" :loading="isProcessing" @confirm="updateStatus(9)">
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-slate-700 text-white text-sm font-bold rounded-xl hover:bg-slate-800 shadow-lg transition-all uppercase">Đã Nhận Hàng Trả</button>
                            </template>
                        </ConfirmAction>
                    </template>

                    <template v-else-if="canRefund && can('orders.approve')">
                        <ConfirmAction
                            title="Xác nhận hoàn tiền" 
                            :message="order.payment_method === 'banking' ? 'Hệ thống sẽ tự động gọi API hoàn tiền VNPAY. Bạn chắc chắn chứ?' : 'Xác nhận bạn đã chuyển khoản hoàn tiền thủ công cho khách?'" 
                            confirmText="Xác nhận hoàn tiền" 
                            :loading="isProcessing" 
                            @confirm="updateStatus(10)"
                        >
                            <template #trigger>
                                <button type="button" class="px-5 py-2.5 bg-teal-600 text-white text-sm font-bold rounded-xl hover:bg-teal-700 shadow-lg shadow-teal-500/20 transition-all uppercase">
                                    {{ order.payment_method === 'banking' ? 'Hoàn Tiền VNPAY' : 'Xác Nhận Đã Hoàn Tiền' }}
                                </button>
                            </template>
                        </ConfirmAction>
                    </template>
                    <div v-else class="flex items-center text-gray-400 italic text-sm bg-gray-100 px-4 py-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Đơn hàng đã hoàn thành, không thể thay đổi!
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <ComponentCard title="Thông tin tài khoản khách hàng">
                <div class="flex items-center gap-6 p-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-2 flex-1">
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-bold">Họ và tên</p>
                            <p class="font-bold text-gray-800 text-md">{{ order.user?.full_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-bold">Email liên hệ</p>
                            <p class="text-gray-700">{{ order.user?.email }}</p>
                        </div>
                    </div>
                </div>
            </ComponentCard>

            <ComponentCard title="Chi tiết đơn hàng & Vận chuyển">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 text-sm">
                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-600 uppercase text-xs tracking-widest border-b pb-1">Thông tin</h4>
                        <div class="space-y-2">
                            <p class="flex justify-between font-medium">
                                <span class="text-gray-500">Đơn hàng:</span>
                                <span class="text-gray-800">#{{ order.id }}</span>
                            </p>
                            <p class="flex justify-between font-medium">
                                <span class="text-gray-500">Mã đơn hàng:</span>
                                <span class="text-gray-800">{{ order.order_code }}</span>
                            </p>
                            <p class="flex justify-between font-medium">
                                <span class="text-gray-500">Ngày đặt:</span>
                                <span class="font-medium text-gray-800">{{ formatDateTime(order.created_at) }}</span> 
                            </p>
                        </div>
                    </div>
                    
                
                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-600 uppercase text-xs tracking-widest border-b pb-1">Người nhận hàng</h4>
                        <div class="space-y-2">
                            <p class="font-bold text-gray-800">{{ order.shipping_recipient_name }}</p>
                            <p class="font-medium text-gray-800">{{ order.shipping_phone_number }}</p>
                            <p class="font-medium text-gray-800 leading-relaxed">
                                {{ order.shipping_address_detail }}, {{ order.shipping_ward }}, {{ order.shipping_district }}, {{ order.shipping_province }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-600 uppercase text-xs tracking-widest border-b pb-1">
                            Ưu đãi & Thanh toán
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between font-medium">
                                <span class="text-gray-500">Phương thức thanh toán:</span>
                                <span class="font-medium text-gray-800 uppercase">{{ order.payment_method }}</span>
                            </div>
                            <div class="flex justify-between font-medium">
                                <span class="text-gray-500">Mã Voucher:</span>
                                <span v-if="order.voucher" class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded font-black">{{ order.voucher.code }}</span>
                                <span v-else class="text-gray-500">Không sử dụng</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-600 uppercase text-xs tracking-widest border-b pb-1">Ghi chú từ khách</h4>
                        <div class="bg-gray-50 p-3 rounded-lg border border-dashed border-gray-200 text-gray-600 min-h-[60px]">
                            {{ order.note || 'Không có ghi chú nào.' }}
                        </div>
                    </div>
                </div>
            </ComponentCard>

            <ComponentCard title="Danh sách sản phẩm trong đơn">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-2">
                        <thead class="text-xs text-gray-600 uppercase font-black px-4">
                            <tr>
                                <th class="pb-3 pl-4">Sản phẩm</th>
                                <th class="pb-3 text-center">Số lượng</th>
                                <th class="pb-3 text-right">Đơn giá</th>
                                <th class="pb-3 text-right pr-4">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in order.details" :key="item.id" class="hover:bg-gray-100 transition-colors">
                                <td class="py-5 pl-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-22 h-32 rounded-xl border border-gray-100 overflow-hidden flex-shrink-0 bg-white p-1 shadow-sm">
                                            <img v-if="item.variant?.primary_thumbnail" :src="item.variant.primary_thumbnail" class="w-full h-full object-cover rounded-lg">
                                        </div>
                                        <div>
                                            <p class="text-md font-bold text-gray-900 leading-tight mb-1">{{ item.product_name }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-bold uppercase">{{ item.variant_info}}</span>
                                                <span v-if="item.flash_sale_id" class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded font-black uppercase flex items-center gap-1">
                                                    Flash Sale
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="text-md font-semibold text-gray-800">{{ item.quantity }}</span>
                                </td>
                                <td class="py-4 text-right">
                                    <span class="text-md font-semibold text-gray-800">{{ formatPrice(item.unit_price) }}</span>
                                </td>
                                <td class="py-4 text-right pr-4">
                                    <span class="text-md font-semibold text-gray-900">{{ formatPrice(item.sub_total) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 flex justify-end">
                    <div class="w-full md:w-80 space-y-3 bg-gray-50/50 p-6 rounded-2xl border border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-md text-gray-800 font-semibold">Tạm tính:</span>
                            <span class="text-md text-gray-800 font-bold">{{ formatPrice(order.total_cost) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-sm text-gray-800 font-semibold">Phí vận chuyển:</span>
                            <span class="text-sm text-gray-800 font-bold">+ {{ formatPrice(order.shipping_fee) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between text-sm">
                            <span class="text-sm text-gray-800 font-semibold">Giảm giá Voucher:</span>
                            <span class="text-sm text-gray-800 font-bold">- {{ formatPrice(order.discount_amount) }}</span>
                        </div>
                        <div class="h-px bg-gray-200 my-2"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-black text-gray-800">Tổng thanh toán:</span>
                            <span class="text-sm font-black text-red-600">{{ formatPrice(order.final_amount) }}</span>
                        </div>
                    </div>
                </div>
            </ComponentCard>
        </div>
    </AdminLayout>
</template>