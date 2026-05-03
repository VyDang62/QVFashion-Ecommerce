<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

import {
  GridIcon, CalenderIcon, UserCircleIcon, PieChartIcon, 
  ChevronDownIcon, HorizontalDots, PageIcon, TableIcon, 
  ListIcon, PlugInIcon,
} from "../../icons";
import SidebarWidget from "./SidebarWidget.vue";
import BoxCubeIcon from "@/icons/BoxCubeIcon.vue";
import { useSidebar } from "@/composables/admin/useSidebar";
import SettingsIcon from "@/icons/SettingsIcon.vue";
import UserGroupIcon from "@/icons/UserGroupIcon.vue";
import ActivityIcon from "@/icons/ActivityIcon.vue";
import OrderIcon from "@/icons/OrderIcon.vue";
import MarketingIcon from "@/icons/MarketingIcon.vue";
import WarehouseIcon from "@/icons/WarehouseIcon.vue";
import { usePage } from "@inertiajs/vue3";
const page = usePage();
const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();

const userPermissions = computed(() => page.props.auth.permissions || []);


const can = (permission) => {
  if (!permission) return true;
  if (Array.isArray(permission)) {
    return permission.some(p => userPermissions.value.includes(p));
  }
  return userPermissions.value.includes(permission);
};

const menuGroups = [
  {
    title: "Tổng quan & Báo cáo",
    items: [
      {
        icon: GridIcon,
        name: "Bảng điều khiển",
        subItems: [
          { name: "Thống kê chính", routeName: "admin.dashboard.overall", permission: 'dashboard.view_overall_dashboard' },
          { name: "Thống kê sản phẩm", routeName: "admin.dashboard.productstatistics", permission: 'dashboard.view_product_statistics' },
          { name: "Thống kê kho hàng", routeName: "admin.dashboard.inventorystatistics", permission: 'dashboard.view_inventory_statistics' },
          { name: "Báo cáo tài chính", routeName: "admin.dashboard.financialreport", permission: 'dashboard.view_financial' },
        ],
      },
      {
        icon: ActivityIcon,
        name: "Nhật ký hoạt động",
        routeName: "admin.activitylogs.index",
        permission: 'activitylogs.view'
      },
    ],
  },
  {
    title: "Quản lý kinh doanh",
    items:[
      {
        icon: OrderIcon,
        name: "Quản lý đơn hàng",
        routeName: "admin.orders.index",
        permission: 'orders.view'
      },
      {
        icon: MarketingIcon,
        name: "Marketing & Sale",
        subItems:[
          { name: "Flash Sale", routeName: "admin.flashsales.index", permission: 'flashsales.view' },
          { name: "Mã giảm giá", routeName: "admin.vouchers.index", permission: 'vouchers.view' },
          { name: "Banner quảng cáo", routeName: "admin.banners.index", permission: 'banners.view' },
        ],
      },
      {
        icon: BoxCubeIcon,
        name: "Quản lý sản phẩm",
        routeName: "admin.userprofile.index",
        subItems: [
          { name: "Danh sách sản phẩm", routeName: "admin.products.index", permission: 'products.view' },
          { name: "Danh mục", routeName: "admin.categories.index", permission: 'categories.view' },
          { name: "Thương hiệu", routeName: "admin.brands.index", permission: 'brands.view' },
          { name: "Thuộc tính", routeName: "admin.attributes.index", permission: 'attributes.view' },
          { name: "Loại sản phẩm", routeName: "admin.producttypes.index", permission: 'product-types.view' },
          { name: "Đánh giá khách hàng", routeName: "admin.ratings.index", permission: 'ratings.view' },
        ],
      },
    ]
  },
  {
    title: "Kho hàng & Đối tác",
    items:[
      {
        icon: WarehouseIcon,
        name: "Quản lý nhập kho",
        subItems: [
          { name: "Nhà cung cấp", routeName: "admin.suppliers.index", permission: 'suppliers.view' },
          { name: "Phiếu nhập hàng", routeName: "admin.goodsreceipts.index", permission: 'goods-receipts.view' },
          { name: "Quản lý lô hàng", routeName: "admin.inventory.batches", permission: 'inventory.view' },
        ],
      },
    ],
  },
  {
    title: "Người dùng & Phân quyền",
    items: [
      {
        icon: UserGroupIcon,
        name: "Quản lý người dùng",
        subItems: [
          { name: "Tài khoản người dùng", routeName: "admin.users.index", permission: 'users.view' },
          { name: "Vai trò & Quyền", routeName: "admin.roles.index", permission: 'roles.view' },
        ],
      },
    ],
  },
  {
    title: "Cấu hình hệ thống",
    items: [
      {
        icon: SettingsIcon,
        name: "Cấu hình website",
        subItems: [
          { name: "Cấu hình", routeName: "admin.settings.index", permission: 'settings.view' },
        ],
      },
      {
        icon: PageIcon,
        name: "Trang tĩnh (CMS)",
        routeName: "admin.pages.index",
        permission: 'pages.view'
      },
      {
        icon: UserCircleIcon,
        name: "Thông tin tài khoản",
        routeName: "admin.userprofile.index",
        permission: null
      },
    ],
  },
];
//Lọc menu dựa trên quyền
const filteredMenu = computed(() => {
  return menuGroups
    .map(group => {
      const filteredItems = group.items
        .map(item => {
          //Nếu có subItems, lọc subItems trước
          if (item.subItems) {
            const visibleSubItems = item.subItems.filter(sub => can(sub.permission));
            return visibleSubItems.length > 0 ? { ...item, subItems: visibleSubItems } : null;
          }
          //Nếu không có subItems, kiểm tra quyền của chính item đó
          return can(item.permission) ? item : null;
        })
        .filter(item => item !== null);

      return filteredItems.length > 0 ? { ...group, items: filteredItems } : null;
    })
    .filter(group => group !== null);
});

const isActive = (routeName) => {
  if (!routeName) return false;
  try {
    return route().current(routeName + '*');
  } catch (e) {
    return false;
  }
};

const toggleSubmenu = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`;
  openSubmenu.value = openSubmenu.value === key ? null : key;
};

const isSubmenuOpen = (groupIndex, itemIndex) => {
  //Kiểm tra xem group có tồn tại không
  const group = filteredMenu.value[groupIndex];
  if (!group) return false;

  //Kiểm tra xem item trong group đó có tồn tại không
  const item = group.items[itemIndex];
  if (!item) return false;

  const key = `${groupIndex}-${itemIndex}`;
  
  //Nếu đang mở thủ công
  if (openSubmenu.value === key) return true;

  //Nếu route con đang active thì tự động mở
  return item.subItems?.some((subItem) => subItem.routeName && isActive(subItem.routeName));
};

const startTransition = (el) => {
  el.style.height = "auto";
  const height = el.scrollHeight;
  el.style.height = "0px";
  el.offsetHeight; 
  el.style.height = height + "px";
};

const endTransition = (el) => { el.style.height = ""; };
</script>

<template>
  <aside
    :class="['fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
      { 'lg:w-[290px]': isExpanded || isMobileOpen || isHovered, 'lg:w-[90px]': !isExpanded && !isHovered, 'translate-x-0 w-[290px]': isMobileOpen, '-translate-x-full': !isMobileOpen, 'lg:translate-x-0': true }]"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <div :class="['py-8 flex', !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start']">
      <Link :href="route('admin.home')"> 
        <img v-if="isExpanded || isHovered || isMobileOpen" class="dark:hidden" :src="$page.props.settings.logo" alt="Logo" width="150" height="40" />
        <img v-else :src="$page.props.settings.logo" alt="Logo" width="32" height="32" />
      </Link>
    </div>
    <div class="flex flex-col overflow-y-auto no-scrollbar">
      <nav class="mb-6">
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in filteredMenu" :key="groupIndex">
            <h2 :class="['mb-4 text-xs uppercase flex text-gray-400', !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start']">
              <template v-if="isExpanded || isHovered || isMobileOpen">{{ menuGroup.title }}</template>
              <HorizontalDots v-else />
            </h2>

            <ul class="flex flex-col gap-4">
              <li v-for="(item, index) in menuGroup.items" :key="item.name">
                <button v-if="item.subItems" @click="toggleSubmenu(groupIndex, index)"
                  :class="['menu-item group w-full', isSubmenuOpen(groupIndex, index) ? 'menu-item-active' : 'menu-item-inactive', !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start']">
                  <span :class="[isSubmenuOpen(groupIndex, index) ? 'menu-item-icon-active' : 'menu-item-icon-inactive']">
                    <component :is="item.icon" />
                  </span>
                  <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">{{ item.name }}</span>
                  <ChevronDownIcon v-if="isExpanded || isHovered || isMobileOpen" :class="['ml-auto w-5 h-5 transition-transform', { 'rotate-180 text-brand-500': isSubmenuOpen(groupIndex, index) }]" />
                </button>

                <Link v-else-if="item.routeName" :href="route(item.routeName)"
                  :class="['menu-item group', isActive(item.routeName) ? 'menu-item-active' : 'menu-item-inactive']">
                  <span :class="[isActive(item.routeName) ? 'menu-item-icon-active' : 'menu-item-icon-inactive']">
                    <component :is="item.icon" />
                  </span>
                  <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">{{ item.name }}</span>
                </Link>

                <transition @enter="startTransition" @after-enter="endTransition" @before-leave="startTransition" @after-leave="endTransition">
                  <div v-show="isSubmenuOpen(groupIndex, index) && (isExpanded || isHovered || isMobileOpen)">
                    <ul class="mt-2 space-y-1 ml-9">
                      <li v-for="subItem in item.subItems" :key="subItem.name">
                        <Link :href="subItem.routeName ? route(subItem.routeName) : '#'"
                          :class="['menu-dropdown-item', isActive(subItem.routeName) ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive']">
                          {{ subItem.name }}
                        </Link>
                      </li>
                    </ul>
                  </div>
                </transition>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>
  </aside>
</template>