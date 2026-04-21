<template>
  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 sm:p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Đơn hàng mới nhất</h3>
      <Link :href="route('admin.orders.index')" class="text-blue-600 text-sm font-medium hover:underline">
        Xem tất cả
      </Link>
    </div>

    <div class="max-w-full overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="border-b border-gray-100 text-left">
            <th class="py-3 text-gray-500 text-xs font-medium uppercase">Mã đơn</th>
            <th class="py-3 text-gray-500 text-xs font-medium uppercase">Khách hàng</th>
            <th class="py-3 text-gray-500 text-xs font-medium uppercase">Tổng tiền</th>
            <th class="py-3 text-gray-500 text-xs font-medium uppercase">Thanh toán</th>
            <th class="py-3 text-gray-500 text-xs font-medium uppercase">Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id" class="border-b border-gray-50 last:border-0">
            <td class="py-4 text-sm font-bold text-blue-600">#{{ order.order_code}}</td>
            <td class="py-4">
              <div class="text-sm font-medium text-gray-800">{{ order.shipping_recipient_name }}</div>
              <div class="text-xs text-gray-500">{{ order.shipping_phone_number }}</div>
            </td>
            <td class="py-4 text-sm font-bold text-gray-900">{{ formatPrice(order.final_amount) }}</td>
            <td class="py-4 text-xs text-gray-600 uppercase">{{ order.payment_method }}</td>
            <td class="py-4">
               <Badge :color="order.status_info.badge_admin">{{ order.status_info.label }}</Badge>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useFormatter } from '@/composables/useFormatter'
import Badge from '@/components/admin/ui/Badge.vue'
const { formatPrice } = useFormatter()
defineProps({ orders: Array })
</script>