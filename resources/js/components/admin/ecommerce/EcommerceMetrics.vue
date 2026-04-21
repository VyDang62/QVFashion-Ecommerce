<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
    <div v-for="metric in metricItems" :key="metric.label" 
         class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <span class="text-sm text-gray-500 font-medium">{{ metric.label }}</span>
          
          <div class="mt-2 flex items-center gap-3">
            <h4 class="font-bold text-gray-800 text-title-sm">
              {{ metric.isPrice ? formatPrice(metric.value) : metric.value }}
            </h4>
          </div>
          <div class="">
            <p class="flex items-center mt-2 text-xs text-gray-400 font-semibold">
              <Badge 
              v-if="metric.growth !== undefined"
              :color="metric.growth >= 0 ? 'success' : 'error'"
              variant="light"
              size="sm"
              :startIcon="metric.growth >= 0 ? IconArrowUp : IconArrowDown"
            >
              {{ Math.abs(metric.growth) }}%
            </Badge>
            So với tháng trước
          </p>
          </div>
          
        </div>

        <div :class="['flex items-center justify-center w-12 h-12 rounded-xl shrink-0', metric.iconBg, metric.iconColor]">
          <component :is="'svg'" v-html="metric.icon" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5"></component>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import Badge from '@/components/admin/ui/Badge.vue'
import { useFormatter } from '@/composables/useFormatter'


const IconArrowUp = {
  render: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 20 20', fill: 'currentColor', class: 'w-3 h-3' }, [
    h('path', { 'fill-rule': 'evenodd', d: 'M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.57a.75.75 0 01-1.08-1.04l5.25-5.25a.75.75 0 011.08 0l5.25 5.25a.75.75 0 11-1.08 1.04L10.75 5.612V16.25A.75.75 0 0110 17z', 'clip-rule': 'evenodd' })
  ])
}

const IconArrowDown = {
  render: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 20 20', fill: 'currentColor', class: 'w-3 h-3' }, [
    h('path', { 'fill-rule': 'evenodd', d: 'M10 3a.75.75 0 01.75.75v10.638l3.96-3.958a.75.75 0 111.08 1.04l-5.25 5.25a.75.75 0 01-1.08 0l-5.25-5.25a.75.75 0 111.08-1.04l3.96 3.958V3.75A.75.75 0 0110 3z', 'clip-rule': 'evenodd' })
  ])
}

const metricItems = computed(() => [
  {
    label: 'Khách hàng',
    value: props.stats?.monthly_users,
    growth: props.stats?.users_growth,
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-600',
    icon: '<path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z" />'
  },
  {
    label: 'Đơn hàng',
    value: props.stats?.monthly_orders,
    growth: props.stats?.orders_growth,
    iconBg: 'bg-green-50',
    iconColor: 'text-green-600',
    icon: '<path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0z" />'
  },
  {
    label: 'Doanh thu',
    value: props.stats?.monthly_revenue,
    growth: props.stats?.revenue_growth,
    isPrice: true,
    iconBg: 'bg-purple-50',
    iconColor: 'text-purple-600',
    icon: '<path d="M2.25 18.75a60.07 60.07 0 0 1 15.75 1.025m-15.75-1.025c0-1.513.68-3.003 2.018-3.812m-2.018 3.812 1.333-2.042m14.417 2.042c0-1.513-.68-3.003-2.018-3.812m2.018 3.812-1.333-2.042m-9.612-3.158a4.912 4.912 0 0 1 6.236 0m-6.236 0L9.448 8.332a4.887 4.887 0 0 0-1.2 3.124m6.236 0c.667-1.133 1.5-2.143 2.454-3.004M15.5 12.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />'
  },
  {
    label: 'Lợi nhuận',
    value: props.stats?.monthly_profit,
    growth: props.stats?.profit_growth,
    isPrice: true,
    iconBg: 'bg-orange-50',
    iconColor: 'text-orange-600',
    icon: '<path d="M2.25 18 9 11.25l4.306 4.307a11.95 11.95 0 0 1 5.814-5.519l2.74-1.22m0 0-5.94-2.28m5.94 2.28-2.28 5.941" />'
  }
])

const { formatPrice } = useFormatter()
const props = defineProps({ stats: Object })

</script>