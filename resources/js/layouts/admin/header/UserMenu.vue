<script setup>
import { UserCircleIcon, ChevronDownIcon, LogoutIcon, SettingsIcon, InfoCircleIcon } from '@/icons'
import { Link, usePage, useForm } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { route } from "ziggy-js";
const page = usePage()
const user = computed(() => page.props.auth.user)
const form = useForm({})

const userInitial = computed(() => {
    return user.value?.full_name 
        ? user.value.full_name.charAt(0).toUpperCase() 
        : 'U';
});

const dropdownOpen = ref(false)
const dropdownRef = ref(null)

const menuItems = [
  { href: 'admin.userprofile.index', icon: UserCircleIcon, text: 'Tài khoản' },
  { href: 'admin.userprofile.index', icon: InfoCircleIcon, text: 'Hỗ trợ' },
]

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const handleLogout = () => {
  form.post(route('admin.logout'), {
    onFinish: () => closeDropdown(),
  })
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

</script>
<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="flex items-center text-gray-700"
      @click.prevent="toggleDropdown"
    >
      <span class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white font-bold text-lg shadow-sm">
        {{ userInitial }}
      </span>

      <span class="block mr-1 font-medium text-theme-sm">{{ user?.full_name }}</span>

      <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
    </button>

    <!-- Dropdown Start -->
    <div
      v-if="dropdownOpen"
      class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg"
    >
      <div>
        <span class="block font-medium text-gray-700 text-theme-sm">
          {{user?.full_name}}
        </span>
        <span class="mt-0.5 block text-theme-xs text-gray-500">
          {{user?.email}}
        </span>
      </div>

      <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200">
        <li v-for="item in menuItems" :key="item.href">
          <Link
            :href="route(item.href)"
            class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700"
          >
            <!-- SVG icon would go here -->
            <component
              :is="item.icon"
              class="text-gray-500 group-hover:text-gray-700"
            />
            {{ item.text }}
          </Link>
        </li>
      </ul>
      <Link
        to="/signin"
        @click="handleLogout"
        class="flex items-center gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700"
      >
        <LogoutIcon
          class="text-gray-500 group-hover:text-gray-700"
        />
        Đăng xuất
      </Link>
    </div>
    <!-- Dropdown End -->
  </div>
</template>


