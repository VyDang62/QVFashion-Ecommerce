<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  headers: {
    type: Array,
    required: true
  },
  items: {
    type: Array,
    default: () => []
  },
  pagination: {
    type: Object,
    default: () => ({})
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  }
})

const searchTerm = defineModel('search', { default: '' })
const perPage = defineModel('perPage', { default: 10 })
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white">
    <div class="px-6 py-5 border-b border-gray-100">
      <h3 class="text-base font-medium text-gray-800">{{ title }}</h3>
    </div>

    <div class="p-4 sm:p-6">
      <div class="space-y-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Show</span>
            <select
                v-model="perPage"
                class="h-9 py-2 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-transparent focus:border-brand-300"
            >
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <span class="text-sm text-gray-500">entries</span>
          </div>

          <div class="relative w-full sm:w-[300px]">
            <input 
              v-model="searchTerm"
              type="text" 
              :placeholder="searchPlaceholder || 'Search...'" 
              class="h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm focus:border-brand-300"
            />
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">
              <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z"/></svg>
            </span>
          </div>
        </div>

        <div class="max-w-full overflow-x-auto rounded-xl border border-gray-100">
          <table class="min-w-full">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/50">
                <th v-for="header in headers" :key="header" class="px-5 py-3 text-left">
                  <p class="text-sm font-bold uppercase tracking-wider text-gray-1000">{{ header }}</p>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in items" :key="index">
                <slot name="row" :item="item"></slot>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pt-4 border-t border-gray-100">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500">
              Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
            </p>
            <div class="flex items-center gap-1">
              <Link 
                v-for="link in pagination.links" :key="link.label"
                :href="link.url || '#'"
                :is="link.url ? 'Link' : 'span'"
                v-html="link.label"
                class="flex h-10 min-w-[40px] items-center justify-center rounded-lg border px-3 text-sm font-medium transition-colors"
                :class="[
                  link.active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 border-gray-300 hover:bg-gray-50',
                  !link.url ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                preserve-scroll
                preserve-state
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>