<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 overflow-hidden">
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between sm:items-center">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">Phân tích Tài chính</h3>
        <p class="text-gray-500 text-sm">Doanh thu & Lợi nhuận thực tế</p>
      </div>
      
      <div class="flex items-center gap-3">
        <div class="inline-flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5">
          <button
            v-for="mode in [
              { value: 'monthly', label: 'Tháng' },
              { value: 'quarterly', label: 'Quý' },
              { value: 'annually', label: 'Năm' }
            ]"
            :key="mode.value"
            @click="updateFilter(filters.year, mode.value)"
            :class="[
              filters.period === mode.value
                ? 'bg-white shadow-sm text-gray-900'
                : 'text-gray-500 hover:text-gray-700',
              'px-3 py-1.5 font-medium rounded-md text-xs transition-all'
            ]"
          >
            {{ mode.label }}
          </button>
        </div>

        <select 
          :value="filters.year" 
          @change="(e) => updateFilter(e.target.value, filters.period)"
          class="text-xs border-gray-200 rounded-lg bg-white py-1.5 focus:ring-blue-500"
        >
          <option v-for="y in filters.years" :key="y" :value="y">Năm {{ y }}</option>
        </select>
      </div>
    </div>
    
    <div class="w-full">
      <VueApexCharts 
        type="area" 
        height="310" 
        width="100%" 
        :options="chartOptions" 
        :series="series" 
      />
    </div>
  </div>
</template>

<script setup>
import VueApexCharts from 'vue3-apexcharts'
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ 
  analytics: Object,
  filters: Object 
})

const updateFilter = (year, period) => {
  router.get(route('admin.dashboard.overall'), 
    { year, period }, 
    { preserveState: true, preserveScroll: true }
  )
}

const series = computed(() => [
  { name: 'Doanh thu', data: props.analytics.revenue },
  { name: 'Lợi nhuận', data: props.analytics.profit }
])

const chartOptions = computed(() => ({
  colors: ['#465FFF', '#10B981'],
  chart: { 
    fontFamily: 'Outfit, sans-serif', 
    toolbar: { show: false },
  },
  stroke: { curve: 'smooth', width: [2, 2] },
  fill: {
    type: 'gradient',
    gradient: { opacityFrom: 0.4, opacityTo: 0 }
  },
  xaxis: { 
    // Dùng nhãn (labels) từ Server gửi xuống (Ví dụ: T1-T12 hoặc Q1-Q4)
    categories: props.analytics.labels,
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  grid: {
    padding: { left: 10, right: 10, bottom: 0 }
  },
  tooltip: {
    y: {
      formatter: (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val)
    }
  }
}))
</script>