<template>
  <Head title="Thống kê chính" />
  <AdminLayout>
    <PageBreadcrumb pageTitle="Thống kê chính" />
    <div class="grid grid-cols-12 gap-4 md:gap-6">
      <div class="col-span-12 xl:col-span-7">
        <EcommerceMetrics :stats="stats" />
      </div>
      
      <div class="col-span-12 space-y-6 xl:col-span-5">
        <MonthlySale :chart-data="monthlySalesData" :filters="filters"/>
      </div>

      <div class="col-span-12">
        <StatisticsChart :analytics="analyticsData" :filters="filters"/>
      </div>

      <div class="col-span-12">
        <MonthlyUser :chart-data="monthlyUsersData" :filters="filters" />
      </div>
      <div class="col-span-12">
        <RecentOrders :orders="recentOrders" />
      </div>
    </div>
  </AdminLayout>
</template>
<script setup lang="ts">
import EcommerceMetrics from '@/components/admin/ecommerce/EcommerceMetrics.vue'
import MonthlySale from '@/components/admin/ecommerce/MonthlySale.vue'
import StatisticsChart from '@/components/admin/ecommerce/StatisticsChart.vue'
import RecentOrders from '@/components/admin/ecommerce/RecentOrders.vue'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import MonthlyUser from '@/components/admin/ecommerce/MonthlyUser.vue'
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  stats: Object,
  filters: Object,
  monthlyUsersData: Object,
  monthlySalesData: Object,
  analyticsData: Object,
  recentOrders: Array
});


</script>
