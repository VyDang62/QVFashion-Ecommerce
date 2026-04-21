<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div className="space-y-5 sm:space-y-6">
      <ComponentCard title="Responsive Image">
        <ResponsiveImage />
      </ComponentCard>
      <ComponentCard title="Image in 2 Grid">
        <TwoColumnImageGrid />
      </ComponentCard>
      <ComponentCard title="Image in 3 Grid">
        <ThreeColumnImageGrid />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import ComponentCard from '@/components/admin/common/ComponentCard.vue'
import ResponsiveImage from '@/components/admin/ui/images/ResponsiveImage.vue'
import TwoColumnImageGrid from '@/components/admin/ui/images/TwoColumnImageGrid.vue'
import ThreeColumnImageGrid from '@/components/admin/ui/images/ThreeColumnImageGrid.vue'
const currentPageTitle = ref('Images')
</script>

<style></style>
