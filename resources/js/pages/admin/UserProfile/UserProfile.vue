<template>
  <admin-layout>
    <Head title="Thông tin tài khoản"/>
    <PageBreadcrumb pageTitle="Thông tin tài khoản" />

    <div
      class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6"
    >
      <h3 class="mb-5 text-lg font-semibold text-gray-800 lg:mb-7">Profile</h3>
      <profile-card/>
      <personal-info-card/>
      <address-card/>
      <password-card/>
    </div>
  </admin-layout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue'
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import ProfileCard from './Partials/ProfileCard.vue'
import PersonalInfoCard from './Partials/PersonalInfoCard.vue'
import AddressCard from './Partials/AddressCard.vue'
import PasswordCard from './Partials/PasswordCard.vue'
</script>
