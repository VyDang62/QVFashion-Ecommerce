<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Line Chart 1">
        <LineChartOne />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import LineChartOne from "@/components/admin/charts/LineChart/LineChartOne.vue";
import ComponentCard from "@/components/admin/common/ComponentCard.vue";
import PageBreadcrumb from "@/components/admin/common/PageBreadcrumb.vue";
import AdminLayout from "@/layouts/admin/AdminLayout.vue";
import { ref } from "vue";
const currentPageTitle = ref("Line Chart");
</script>
