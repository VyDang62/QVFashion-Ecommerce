<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import { route } from 'ziggy-js';
interface BreadcrumbProps {
  pageTitle: string
  parentName?: string
  parentRoute?: string
}

defineProps<BreadcrumbProps>()
</script>


<template>
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h2 class="text-xl font-semibold text-gray-800" x-text="pageTitle">
      {{ pageTitle }}
    </h2>
    <nav>
      <ol class="flex items-center gap-1.5">
        <li>
          <Link
            :href="route('admin.home')"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500"
          >
            Home
            <svg
              class="stroke-current"
              width="17"
              height="16"
              viewBox="0 0 17 16"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                stroke=""
                stroke-width="1.2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </Link>
        </li>
        <li v-if="parentName && parentRoute">
          <Link
            :href="parentRoute"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500"
          >
          {{parentName}}
            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
              <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </Link>
        </li>
        <li class="text-sm text-gray-800">
          {{ pageTitle }}
        </li>
      </ol>
    </nav>
  </div>
</template>

