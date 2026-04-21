<template>
  <div class="rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="px-5 pt-5 bg-white shadow-sm rounded-2xl pb-11 sm:px-6 sm:pt-6">
      <div class="flex justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-800">Mục tiêu tháng</h3>
          <p class="mt-1 text-gray-500 text-theme-sm">
            Tiến độ hoàn thành doanh thu so với mục tiêu đề ra
          </p>
        </div>
        <div>
          <DropdownMenu :menu-items="menuItems">
            <template #icon>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.2441 6C10.2441 5.0335 11.0276 4.25 11.9941 4.25H12.0041C12.9706 4.25 13.7541 5.0335 13.7541 6C13.7541 6.9665 12.9706 7.75 12.0041 7.75H11.9941C11.0276 7.75 10.2441 6.9665 10.2441 6ZM10.2441 18C10.2441 17.0335 11.0276 16.25 11.9941 16.25H12.0041C12.9706 16.25 13.7541 17.0335 13.7541 18C13.7541 18.9665 12.9706 19.75 12.0041 19.75H11.9941C11.0276 19.75 10.2441 18.9665 10.2441 18ZM11.9941 10.25C11.0276 10.25 10.2441 11.0335 10.2441 12C10.2441 12.9665 11.0276 13.75 11.9941 13.75H12.0041C12.9706 13.75 13.7541 12.9665 13.7541 12C13.7541 11.0335 12.9706 10.25 12.0041 10.25H11.9941Z" fill="currentColor" />
              </svg>
            </template>
          </DropdownMenu>
        </div>
      </div>

      <div class="relative max-h-[195px]">
        <div id="chartTwo" class="h-full">
          <div class="radial-bar-chart">
            <VueApexCharts type="radialBar" height="330" :options="chartOptions" :series="[progressValue]" />
          </div>
        </div>
        <span class="absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-600">
          +{{ progressValue.toFixed(1) }}%
        </span>
      </div>

      <p class="mx-auto mt-1.5 w-full max-w-[380px] text-center text-sm text-gray-500">
        Bạn đã đạt được {{ formatPrice(targetData.revenue) }} trong tháng này. 
        {{ progressValue >= 100 ? 'Tuyệt vời! Bạn đã vượt mục tiêu.' : 'Cố gắng lên, bạn sắp đạt mục tiêu rồi!' }}
      </p>
    </div>

    <div class="flex items-center justify-center gap-5 px-6 py-3.5 sm:gap-8 sm:py-5">
      <div class="text-center">
        <p class="mb-1 text-gray-500 text-theme-xs">Mục tiêu</p>
        <p class="font-bold text-gray-800 text-sm sm:text-base">
          {{ formatPrice(targetData.target_amount) }}
        </p>
      </div>

      <div class="w-px bg-gray-200 h-7"></div>

      <div class="text-center">
        <p class="mb-1 text-gray-500 text-theme-xs">Đã đạt</p>
        <p class="font-bold text-blue-600 text-sm sm:text-base">
          {{ formatPrice(targetData.revenue) }}
        </p>
      </div>

      <div class="w-px bg-gray-200 h-7"></div>

      <div class="text-center">
        <p class="mb-1 text-gray-500 text-theme-xs">Hôm nay</p>
        <p class="font-bold text-green-600 text-sm sm:text-base">
          {{ formatPrice(targetData.today) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import DropdownMenu from '../common/DropdownMenu.vue'
import VueApexCharts from 'vue3-apexcharts'
import { useFormatter } from '@/composables/useFormatter'

const { formatPrice } = useFormatter()

const props = defineProps({
  targetData: {
    type: Object,
    default: () => ({
      target_amount: 100000000, 
      revenue: 75000000,
      today: 3500000,
      value: 75
    }),
  },
})

const progressValue = computed(() => {
  if (props.targetData.value) return props.targetData.value
  return (props.targetData.revenue / props.targetData.target_amount) * 100
})

const menuItems = [
  { label: 'Chỉnh sửa mục tiêu', onClick: () => console.log('Edit Target') },
  { label: 'Làm mới', onClick: () => window.location.reload() },
]

const chartOptions = {
  colors: ['#465FFF'],
  chart: {
    fontFamily: 'Outfit, sans-serif',
    sparkline: { enabled: true },
  },
  plotOptions: {
    radialBar: {
      startAngle: -90,
      endAngle: 90,
      hollow: { size: '80%' },
      track: {
        background: '#E4E7EC',
        strokeWidth: '100%',
        margin: 5,
      },
      dataLabels: {
        name: { show: false },
        value: {
          fontSize: '32px',
          fontWeight: '700',
          offsetY: 40,
          color: '#1D2939',
          formatter: (val: number) => val.toFixed(1) + '%',
        },
      },
    },
  },
  fill: { type: 'solid', colors: ['#465FFF'] },
  stroke: { lineCap: 'round' },
  labels: ['Tiến độ'],
}
</script>

<style scoped>
.radial-bar-chart {
  width: 100%;
  max-width: 330px;
  margin: 0 auto;
}
</style>