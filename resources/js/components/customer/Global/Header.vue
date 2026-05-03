<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import { useFormatter } from '@/composables/useFormatter';
//states
const mobileMenuOpen = ref(false);
const isSearchOpen = ref(false);
const isUserDropdownOpen = ref(false);
const isCartOpen = ref(false);

const searchQuery = ref('');
const searchInput = ref(null);
const searchInputMobile = ref(null);

const activeGender = ref(null);
const activeProductType = ref(null);
const activeParentCategory = ref(null);

//toggle dropdowns
const toggleGender = (gender) => {
  activeGender.value = activeGender.value === gender ? null : gender;
  activeProductType.value = null;
};

const toggleProductType = (typeId) => {
  activeProductType.value = activeProductType.value === typeId ? null : typeId;
  activeParentCategory.value = null;
};

const toggleParentCategory = (id) => {
  activeParentCategory.value = activeParentCategory.value === id ? null : id;
};

const toggleSearch = async () => {
  isSearchOpen.value = !isSearchOpen.value;
  if(isSearchOpen.value) {
    isUserDropdownOpen.value = false;
    isCartOpen.value = false;
    await nextTick();
    searchInput.value?.focus();
    searchInputMobile.value?.focus();
  }
};

const toggleUserDropdown = () => {
  isUserDropdownOpen.value = !isUserDropdownOpen.value;
  if(isUserDropdownOpen.value) {
    isSearchOpen.value = false;
    isCartOpen.value = false;
  }
};

const toggleCart = () => {
  isCartOpen.value = !isCartOpen.value;
  if(isCartOpen.value) {
    isSearchOpen.value = false;
    isUserDropdownOpen.value = false;
  }
};

const openCartWithAutoClose = () => {
    isCartOpen.value = true;
    isSearchOpen.value = false;
    isUserDropdownOpen.value = false;

    setTimeout(() => {
        isCartOpen.value = false;
    }, 4000);
};

onMounted(() => {
    window.addEventListener('open-cart-dropdown', openCartWithAutoClose);
});

onUnmounted(() => {
    window.removeEventListener('open-cart-dropdown', openCartWithAutoClose);
});

const removeFromCart = (id) => {
  router.delete(route('cart.destroy', id), {
    preserveScroll: true,
  });
}
//handle search và logout
const handleSearch = () => {
  if (searchQuery.value.trim()) {
    router.get(route('shop.index'), { search: searchQuery.value });
    isSearchOpen.value = false;
    searchQuery.value = '';
    mobileMenuOpen.value = false;
  }
};

const handleLogout = () => {
  router.post(route('logout'));
};

const {formatPrice} = useFormatter();
</script>
<template>
  <header class="bg-gray-dark sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center py-4 px-4 relative">
      
      <Link href="/" class="flex items-center flex-shrink-0">
        <img :src="$page.props.settings.logo" :alt="$page.props.settings.site_name" class="h-10 md:h-14 w-auto">
      </Link>

      <nav class="hidden lg:flex space-x-6 items-center text-white tracking-wide">
        <Link href="/" class="hover:text-secondary font-semibold uppercase">Trang chủ</Link>
        
        <div v-for="(productTypes, gender) in $page.props.menuCategories.data" :key="gender" class="relative group">
          <Link 
            :href="route('shop.index', { slug: gender })" 
            class="hover:text-secondary font-semibold flex items-center cursor-pointer py-4 uppercase"
          >
            {{ $page.props.menuCategories.labels[gender] || gender }}
            <i class="fas fa-chevron-down ml-1 text-[10px] transition-transform duration-300 group-hover:rotate-180"></i>
          </Link>

          <div class="absolute top-full left-1/2 -translate-x-1/2 mt-0 hidden group-hover:grid grid-cols-4 gap-8 bg-white text-black p-8 shadow-2xl w-[1000px] rounded-b-lg border-t-4 border-primary z-50">
            <div v-for="type in productTypes" :key="type.id" class="flex flex-col border-r border-gray-100 last:border-0 pr-4">
              <h3 class="font-black text-gray-600 uppercase text-xs tracking-[0.2em] mb-4">
                <Link :href="route('shop.index', {gender: [gender], slug: type.type_slug })" class="block px-2 py-2 rounded transition-all hover:bg-primary hover:text-white">
                  {{ type.type_name }}
                </Link>
              </h3>
              
              <div v-for="parent in type.categories" :key="parent.id" class="text-left last:border-0">
                <div class="flex justify-between items-center">
                  <Link 
                    :href="route('shop.index', { slug: parent.category_slug })" 
                    class="font-black text-primary px-2 hover:underline text-xs uppercase block flex-1" 
                    @click="mobileMenuOpen = false"
                  >
                    {{ parent.category_name }}
                  </Link>
                  <button 
                    v-if="parent.children?.length > 0"
                    @click.stop="toggleParentCategory(parent.id)" 
                    class="p-2 text-gray-400"
                  >
                    <i :class="[
                      'fas fa-chevron-down text-[10px] transition-transform duration-300', 
                      { 'rotate-180': activeParentCategory === parent.id }
                    ]"></i>
                  </button>
                </div>

                <transition name="expand">
                  <ul v-show="activeParentCategory === parent.id" class="space-y-2">
                    <li v-for="child in parent.children" :key="child.id">
                      <Link 
                        :href="route('shop.index', { slug: child.category_slug })" 
                        class="block px-2 text-sm text-gray-600 hover:text-primary hover:underline transition-colors" 
                        @click="mobileMenuOpen = false"
                      >
                        {{ child.category_name }}
                      </Link>
                    </li>
                  </ul>
                </transition>
              </div>
            </div>
          </div>
        </div>
      </nav>

      <div class="hidden lg:flex items-center space-x-5 text-white">
        
        <div class="relative" ref="searchContainer">
          <button @click="toggleSearch" class="hover:text-secondary transition-transform hover:scale-110 flex items-center p-2">
            <img src="/assets/images/search-icon.svg" class="h-6 w-6" alt="Search">
          </button>

          <transition name="pop">
            <div v-if="isSearchOpen" class="absolute right-0 top-full mt-4 w-80 bg-white rounded-xl shadow-2xl p-4 border-t-4 border-primary z-50 text-black">
              <div class="relative flex items-center">
                <input 
                  ref="searchInput"
                  v-model="searchQuery" 
                  @keyup.enter="handleSearch"
                  type="text" 
                  placeholder="Tìm kiếm sản phẩm..." 
                  class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-4 pr-10 text-sm focus:ring-2 focus:ring-primary outline-none"
                >
                <button @click="handleSearch" class="absolute right-3 text-gray-400 hover:text-primary transition-colors">
                  <i class="fas fa-search"></i>
                </button>
              </div>
              <div class="absolute -top-2 right-4 w-4 h-4 bg-white rotate-45 border-l border-t border-gray-100"></div>
            </div>
          </transition>
        </div>

        <template v-if="!$page.props.auth?.user">
          <Link :href="route('login')" class="bg-primary border border-primary hover:bg-transparent text-white hover:text-primary font-semibold px-4 py-2 rounded-full inline-block transition-colors text-sm uppercase">Đăng nhập</Link>
        </template>
        <template v-else>
          <div class="relative">
            <button @click="toggleUserDropdown" class="flex items-center space-x-2 hover:text-secondary transition-colors font-semibold py-2">
              <span class="hidden md:inline-block uppercase tracking-wide text-sm">Hi, {{ $page.props.auth.user.full_name }}</span>
              <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white border-2 border-white/20">
                <i class="fas fa-user text-xs"></i>
              </div>
              <i :class="['fas fa-chevron-down text-[10px] transition-transform duration-300', { 'rotate-180': isUserDropdownOpen }]"></i>
            </button>

            <transition name="pop">
              <div v-if="isUserDropdownOpen" class="absolute min-w-[250px] right-0 top-full mt-4 w-60 bg-white rounded-xl shadow-2xl overflow-hidden border-t-4 border-primary z-50 text-black">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                  <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">Tài khoản</p>
                  <p class="text-sm font-black truncate">{{ $page.props.auth.user.email }}</p>
                </div>
                <div class="p-2 flex flex-col text-gray-700">
                  <Link :href="route('profile.edit')" class="flex items-center space-x-3 px-4 py-3 text-sm hover:bg-primary hover:text-white rounded-lg transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>  
                  <span class="text-md">Thông tin tài khoản</span>
                  </Link>
                  <Link :href="route('wishlist')" class="flex items-center space-x-3 px-4 py-3 text-sm hover:bg-primary hover:text-white rounded-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    <span class="text-md">Sản phẩm yêu thích</span>
                  </Link>
                  <Link :href="route('orderhistory')" class="flex items-center space-x-3 px-4 py-3 text-sm hover:bg-primary hover:text-white rounded-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    <span class="text-md">Đơn hàng của tôi</span>
                  </Link>
                  <button @click="handleLogout" class="flex items-center space-x-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-all w-full text-left">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    <span class="text-md font-bold">Đăng xuất</span>
                  </button>
                </div>
                <div class="absolute -top-2 right-4 w-4 h-4 bg-white rotate-45 border-l border-t border-gray-100"></div>
              </div>
            </transition>
          </div>
        </template>

        <div class="relative">
          <button @click="toggleCart" class="relative hover:scale-110 transition-transform p-2">
            <img src="/assets/images/cart-shopping.svg" class="h-6 w-6" alt="Cart">
            <span v-if="$page.props.cartCount > 0" class="absolute top-0 right-0 bg-primary text-[10px] rounded-full px-1.5 font-bold shadow-sm">
              {{ $page.props.cartCount }}
            </span>
          </button>

          <transition name="pop">
            <div v-if="isCartOpen" class="min-w-[500px] absolute right-0 top-full mt-4 w-96 bg-white rounded-xl shadow-2xl overflow-hidden border-t-4 border-primary z-50 text-black">
              <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black uppercase tracking-widest text-primary">Giỏ hàng</h3>
                <span class="text-xs text-gray-600 font-medium">{{ $page.props.cartCount }} sản phẩm</span>
              </div>

              <div class="max-h-[500px] overflow-y-auto p-2 custom-scrollbar">
                <template v-if="$page.props.cartItems?.length > 0">
                  <div v-for="item in $page.props.cartItems" :key="item.id" class="flex gap-4 p-3 hover:bg-gray-50 rounded-xl transition-colors mb-2 relative group">
                    <button
                      @click.stop="removeFromCart(item.id)"
                      class="absolute top-2 right-2 group-hover:opacity-100 text-gray-300 hover:text-red-500 transition-all p-0.5"
                      title="Xóa sản phẩm"
                    >
                      <i class="fas fa-times-circle text-lg"></i>
                    </button>
                    <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg border border-gray-100 relative">
                      <img :src="item.image" class="h-full w-full object-cover">
                      <span v-if="item.is_flash_sale" class="absolute top-0 left-0 bg-red-600 text-white text-[8px] font-bold px-1 rounded-br-md">SALE</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h4 class="text-md font-bold text-gray-800 uppercase">{{ item.name }}</h4>
                      <p class="text-sm text-gray-600 italic mb-1">{{ item.variant_info }}</p>
                      
                      <div class="flex justify-between items-end">
                        <span class="text-sm text-gray-600 font-bold">x{{ item.quantity }}</span>
                        
                        <div class="text-right">
                          <p v-if="item.is_flash_sale" class="text-md text-gray-400 line-through leading-none mb-0.5">
                            {{ formatPrice(item.price) }}
                          </p>
                          <p class="text-md font-black text-primary leading-none">
                            {{ formatPrice(item.current_price) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
                <div v-else class="py-12 text-center flex flex-col items-center">
                   <i class="fas fa-shopping-basket text-gray-200 text-5xl mb-4"></i>
                   <p class="text-sm text-gray-400 font-medium italic">Giỏ hàng đang trống...</p>
                </div>
              </div>

              <div v-if="$page.props.cartItems?.length > 0" class="p-4 border-t border-gray-100 bg-gray-50/30">
                <div class="flex justify-between items-center mb-4 px-2">
                  <span class="text-sm font-bold uppercase">Tổng tiền:</span>
                  <span class="text-lg font-bold text-primary">{{ formatPrice($page.props.cartTotal) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <Link :href="route('cart.index')" @click="isCartOpen = false" class="text-center py-3 text-sm font-black rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 transition-all duration-300">Xem giỏ hàng</Link>
                  <Link :href="route('checkout')" @click="isCartOpen = false" class="text-center py-3 text-sm font-black rounded-full bg-primary text-white border border-primary shadow-md hover:bg-transparent hover:text-primary transition-all duration-300">Đặt hàng ngay</Link>
                </div>
              </div>
              <div class="absolute -top-2 right-4 w-4 h-4 bg-white rotate-45 border-l border-t border-gray-100"></div>
            </div>
          </transition>
        </div>
      </div>

      <div class="lg:hidden flex items-center space-x-2">
         <div class="relative">
            <button @click="toggleSearch" class="text-white text-xl p-2">
               <i :class="isSearchOpen ? 'fas fa-times' : 'fas fa-search'"></i>
            </button>
            <transition name="pop">
              <div v-if="isSearchOpen" class="absolute right-[-50px] top-full mt-5 w-[90vw] bg-white rounded-xl shadow-2xl p-4 z-50 border-t-4 border-primary">
                <input ref="searchInputMobile" v-model="searchQuery" @keyup.enter="handleSearch" type="text" class="w-full bg-gray-100 border-none rounded-lg py-3 px-4 text-black focus:ring-1 focus:ring-primary outline-none" placeholder="Bạn tìm gì hôm nay?">
              </div>
            </transition>
         </div>
         
         <Link :href="route('cart.index')" class="relative p-2 text-white">
            <img src="/assets/images/cart-shopping.svg" class="h-6 w-6">
            <span v-if="$page.props.cartCount > 0" class="absolute top-0 right-0 bg-primary text-[9px] rounded-full px-1.5 font-bold shadow-md">
              {{ $page.props.cartCount }}
            </span>
         </Link>

         <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white text-2xl p-2">
            <i :class="mobileMenuOpen ? 'fas fa-times' : 'fas fa-bars'"></i>
         </button>
      </div>
    </div>

    <transition name="slide">
      <div v-if="mobileMenuOpen" class="lg:hidden bg-white fixed inset-0 top-[72px] z-40 overflow-y-auto">
        <div class="p-6 flex flex-col space-y-4">
          <Link href="/" @click="mobileMenuOpen = false" class="text-xl text-center font-bold border-b pb-4 uppercase text-gray-800">Trang chủ</Link>
          
          <div v-for="(productTypes, gender) in $page.props.menuCategories.data" :key="gender" class="border-b border-gray-50 pb-2 text-center">
            <button @click="toggleGender(gender)" class="w-full flex justify-center items-center gap-3 py-3 text-xl font-bold uppercase text-gray-800">
              {{ $page.props.menuCategories.labels[gender] || gender }}
              <i :class="['fas fa-chevron-down text-xs transition-transform', { 'rotate-180': activeGender === gender }]"></i>
            </button>
            
            <transition name="expand">
              <div v-show="activeGender === gender" class="bg-gray-50 rounded-xl p-2">
                <div v-for="type in productTypes" :key="type.id" class="my-2 border-b border-gray-100 last:border-0">
                  <button @click="toggleProductType(type.id)" class="w-full flex justify-between items-center py-3 px-3 text-lg font-bold text-gray-700">
                    {{ type.type_name }}
                    <i :class="['fas fa-plus text-[10px] transition-transform', { 'rotate-45': activeProductType === type.id }]"></i>
                  </button>
                  <div v-show="activeProductType === type.id" class="pl-4 pb-4">
                    <div v-for="parent in type.categories" :key="parent.id" class="mt-4 text-left">
                      <Link :href="route('shop.index', { slug: parent.category_slug })" class="font-black text-primary text-xs uppercase block mb-2" @click="mobileMenuOpen = false">
                        {{ parent.category_name }}
                      </Link>
                      <ul class="space-y-1">
                        <li v-for="child in parent.children" :key="child.id">
                          <Link :href="route('shop.index', { slug: child.category_slug })" class="block py-2 text-sm text-gray-600" @click="mobileMenuOpen = false">{{ child.category_name }}</Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <div class="flex flex-col space-y-4 items-center">
            <Link :href="route('cart.index')" @click="mobileMenuOpen = false" class="w-full text-center font-bold py-3 uppercase text-gray-700 border-b border-gray-100">
                Giỏ hàng ({{ $page.props.cartCount }})
            </Link>
            <template v-if="!$page.props.auth?.user">
              <Link :href="route('login')" @click="mobileMenuOpen = false" class="w-full bg-primary text-white text-center font-bold py-4 rounded-full uppercase shadow-lg">Đăng nhập</Link>
            </template>
            <template v-else>
              <Link :href="route('profile.edit')" @click="mobileMenuOpen = false" class="w-full text-center font-bold py-3 uppercase text-gray-700 border-b border-gray-100">Thông tin tài khoản</Link>
              <button @click="handleLogout" class="w-full bg-red-600 text-white text-center font-bold py-4 rounded-full uppercase shadow-md mt-4">Đăng xuất</button>
            </template>
          </div>
        </div>
      </div>
    </transition>
  </header>
</template>



<style scoped>
.pop-enter-active {
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.pop-leave-active {
  transition: all 0.2s ease-in;
}
.pop-enter-from, .pop-leave-to {
  opacity: 0;
  transform: translateY(15px) scale(0.9);
}

.slide-enter-active, .slide-leave-active {
  transition: transform 0.4s ease-in-out;
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}

.expand-enter-active, .expand-leave-active {
  transition: all 0.3s ease-in-out;
  max-height: 500px;
  overflow: hidden;
}
.expand-enter-from, .expand-leave-to {
  max-height: 0;
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #eee;
  border-radius: 10px;
}
</style>