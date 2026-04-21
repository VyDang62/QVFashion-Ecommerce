<template>
  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Đơn hàng</h3>
        <p class="text-xs text-gray-500 mt-1">Số lượng đơn hàng thành công năm {{ filters.year }}</p>
      </div>

      <div class="flex items-center gap-2">
        <select 
          :value="filters.year" 
          @change="updateYear"
          class="text-xs border-gray-200 rounded-lg bg-gray-50 py-1 px-2 focus:ring-blue-500"
        >
          <option v-for="y in filters.years" :key="y" :value="y">Năm {{ y }}</option>
        </select>
      </div>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
      <div id="chartOne" class="-ml-5 min-w-[650px] xl:min-w-full pl-2">
        <VueApexCharts type="bar" height="200" :options="chartOptions" :series="chartData.series" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  chartData: Object,
  filters: Object // Nhận year, period, years từ controller
})

const updateYear = (e) => {
  router.get(route('admin.dashboard.overall'), 
    { year: e.target.value, period: props.filters.period }, 
    { preserveState: true, preserveScroll: true }
  )
}

const chartOptions = computed(() => ({
  colors: ['#465fff'],
  chart: { fontFamily: 'Outfit, sans-serif', toolbar: { show: false } },
  plotOptions: {
    bar: { columnWidth: '40%', borderRadius: 6, borderRadiusApplication: 'end' },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: { labels: { formatter: (val) => Math.floor(val) } },
  tooltip: {
    y: { formatter: (val) => val + " đơn hàng" },
  },
}))
</script>