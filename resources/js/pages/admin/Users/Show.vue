<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import { useFormatter } from '@/composables/useFormatter';
import Badge from '@/components/admin/ui/Badge.vue';
import { UserIcon } from 'lucide-vue-next';

const props = defineProps({
    user: Object
});

const { formatPrice, formatDateTime } = useFormatter();

const totalSpent = computed(() => {
    return props.user.orders?.reduce((sum, order) => sum + parseFloat(order.final_amount || 0), 0) || 0;
});

const successfulOrders = computed(() => {
    return props.user.orders?.filter(order => order.order_status === 6).length || 0;
});

</script>

<template>
    <Head :title="'Người dùng: ' + user.full_name"/>
    <AdminLayout>
        <PageBreadcrumb parentName="Người dùng" :parentRoute="route('admin.users.index')"/>

        <div class="mb-6 flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ user.full_name }}</h2>
                    <div class="flex gap-2 mt-1">
                        <span v-if="user.deleted_at" class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold uppercase">Tài khoản đã xóa</span>
                        <span v-if="!user.is_active" class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-bold uppercase">Bị khóa</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <Link :href="route('admin.users.edit', user.id)" class="text-gray-400 hover:text-blue-600 p-2 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                </Link>
                <DeleteAction 
                    :message="`Bạn có chắc chắn muốn xóa người dùng ${user.full_name}?`" 
                    :item="user" 
                    routeName="admin.users.destroy" 
                    :displayName="user.full_name" 
                />
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-8">
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">Thông tin liên hệ</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"/></svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 font-semibold">Email</p>
                                <p class="text-sm font-medium text-gray-900">{{ user.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-width="2"/></svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 font-semibold">Số điện thoại</p>
                                <p class="text-sm font-medium text-gray-900">{{ user.phone_number || 'Chưa cập nhật' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-900 p-6 rounded-3xl shadow-xl text-white relative overflow-hidden">
                    <svg class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Giá trị khách hàng</h3>
                    <div class="space-y-4 relative z-10">
                        <div>
                            <p class="text-3xl font-black text-white">{{ formatPrice(totalSpent) }}</p>
                            <p class="text-xs text-gray-400 mt-1">Tổng chi tiêu trên hệ thống</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/10">
                            <div>
                                <p class="text-xl font-bold">{{ user.orders?.length || 0 }}</p>
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Đơn hàng</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-emerald-400">{{ successfulOrders }}</p>
                                <p class="text-[10px] text-gray-400 uppercase font-bold">Thành công</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-2">
                    <h3 class="text-lg font-bold text-gray-600">Vai trò & Quyền</h3>
                    <div class="flex flex-wrap gap-2">
                        <Badge v-for="role in user.roles" :key="role.id" color="primary" variant="solid" size="sm">
                            {{ role.name }}
                        </Badge>
                        <p v-if="!user.roles?.length" class="text-xs text-gray-500 italic">Khách hàng thông thường</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <div class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-width="2"/>
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"/>
                        </svg>
                        Địa chỉ mặc định
                    </div>
                    <div v-if="user.province" class="grid sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-600 font-bold mb-1">Địa chỉ chi tiết</p>
                            <p class="text-sm text-gray-900 font-medium">{{ user.address_detail }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-bold mb-1">Khu vực</p>
                            <p class="text-sm text-gray-900 font-medium">
                                {{ user.ward }}, {{ user.district }}, {{ user.province }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="text-gray-600 italic text-sm py-2">Người dùng chưa cập nhật địa chỉ giao hàng.</div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-50 bg-gray-50/30 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2"/></svg>
                            Đơn hàng gần đây
                        </h3>
                        <Link :href="route('admin.orders.index', { user_id: user.id })" class="text-xs font-bold text-blue-600 hover:underline">Xem tất cả</Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50/50 text-xs font-bold text-gray-600 uppercase">
                                <tr>
                                    <th class="px-6 py-4">Mã đơn</th>
                                    <th class="px-6 py-4">Ngày đặt</th>
                                    <th class="px-6 py-4">Tổng tiền</th>
                                    <th class="px-6 py-4 text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                <tr v-for="order in user.orders?.slice(0, 5)" :key="order.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-blue-600">{{ order.order_code }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ formatDateTime(order.order_date) }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-900">{{ formatPrice(order.final_amount) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <Badge variant="solid" size="sm" :color="order.status_info.badge_admin">{{ order.status_info.label }}</Badge>
                                    </td>
                                </tr>
                                <tr v-if="!user.orders?.length">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-600 italic text-sm py-2">Chưa có đơn hàng nào được thực hiện.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" 
                            />
                        </svg>
                        Mã giảm giá đã dùng
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="usage in user.voucher_usages" :key="usage.id" class="bg-amber-50 border border-amber-100 p-2 rounded-xl flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M17.707 9.293l-5-5a1 1 0 00-1.414 0l-7 7a1 1 0 000 1.414l5 5a1 1 0 001.414 0l7-7a1 1 0 000-1.414zM9 11a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                            <span class="text-xs font-bold text-amber-700">-{{ formatPrice(usage.discount_amount) }}</span>
                        </div>
                        <p v-if="!user.voucher_usages?.length" class="text-gray-600 italic text-sm py-2">Chưa sử dụng mã giảm giá.</p>
                    </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Thông tin hệ thống</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600 text-sm">Ngày tham gia:</span>
                                <span class="text-gray-900 text-sm font-medium">{{ formatDateTime(user.created_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 text-sm">Cập nhật cuối:</span>
                                <span class="text-gray-900 text-sm font-medium">{{ formatDateTime(user.updated_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 text-sm">ID hệ thống:</span>
                                <span class="text-gray-900 text-sm font-medium">#{{ user.id }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>