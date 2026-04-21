<script setup>
defineProps({
    headers: Array,
    items: Array
})
</script>

<template>
  <div
    class="overflow-hidden rounded-xl border border-gray-200 bg-white"
    >
    <div class="max-w-full overflow-x-auto custom-scrollbar">
      <table class="min-w-full">
        <thead>
          <tr class="border-b border-gray-200">
            <th v-for="header in headers" :key="header" class="px-5 py-3 text-left w-3/11 sm:px-6">
              <p class="font-bold text-gray-1000 text-theme-sm">{{header}}</p>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(item, index) in items" :key="index"class="border-t border-gray-100">
            <slot :item="item" :index="index"></slot>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>