<template>
  <footer class="border-t border-gray-line">
    <div class="container mx-auto px-4 py-10">
      <div class="flex flex-wrap -mx-4">
        
        <div class="w-full sm:w-1/6 px-4 mb-8">
          <h3 class="text-lg font-semibold mb-4">QVFashion</h3>
          <ul>
            <li v-for="item in dynamicPages" :key="item.slug">
              <Link 
                :href="'/page/' + item.slug" 
                class="hover:text-primary transition-colors py-1 block"
              >
                {{ item.title }}
              </Link>
            </li>
          </ul>
        </div>

        <div class="w-full sm:w-1/6 px-4 mb-8">
          <h3 class="text-lg font-semibold mb-4">Trang</h3>
          <ul>
            <li v-for="item in pagesMenu" :key="item.text">
              <Link :href="item.url" class="hover:text-primary">{{ item.text }}</Link>
            </li>
          </ul>
        </div>

        <div class="w-full sm:w-1/6 px-4 mb-8">
          <h3 class="text-lg font-semibold mb-4">Tài khoản</h3>
          <ul>
            <li v-for="item in accountMenu" :key="item.text">
              <Link :href="item.url" class="hover:text-primary">{{ item.text }}</Link>
            </li>
          </ul>
        </div>

        <div class="w-full sm:w-1/6 px-4 mb-8">
          <h3 class="text-lg font-semibold mb-4">Theo dõi</h3>
          <ul>
            <li v-for="social in socialLinks" :key="social.name" class="flex items-center mb-2">
              <img :src="social.icon" :alt="social.name" class="w-4 h-4 transition-transform transform hover:scale-110 mr-2">
              <a :href="social.url" class="hover:text-primary">{{ social.name }}</a>
            </li>
          </ul>
        </div>

        <div class="w-full sm:w-2/6 px-4 mb-8">
          <h3 class="text-lg font-semibold mb-4">Liên hệ</h3>
          <p><img :src="$page.props.settings.logo" :alt="$page.props.settings.site_name" class="h-[60px] mb-4"></p>
          <p>{{$page.props.settings.address}}</p>
          <p class="text-xl font-bold my-4">Điện thoại: {{$page.props.settings.phone_number}}</p>
          <a href="mailto:info@company.com" class="underline">Email: {{$page.props.settings.contact_email}}</a>
        </div>
      </div>
    </div>

    <div class="py-6 border-t border-gray-line bg-black text-white">
      <div class="container mx-auto px-4 flex flex-wrap justify-between items-center">
        
        <div class="w-full lg:w-3/4 text-center lg:text-left mb-4 lg:mb-0">
          <p class="mb-2 font-bold">&copy; 2026 QVFashion.</p>
          <ul class="flex justify-center lg:justify-start space-x-4 mb-4 lg:mb-0">
            <li v-for="item in dynamicPages.slice(0, 3)" :key="item.slug">
              <Link :href="'/page/' + item.slug" class="hover:text-primary text-sm">
                {{ item.title }}
              </Link>
            </li>
          </ul>
          <p class="text-sm mt-4">{{$page.props.settings.description}}</p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const pagesMenu = [
  { text: 'Mua sắm', url: '/shop' },
  { text: 'Đặt hàng', url: '/checkout' },
];

const accountMenu = [
  { text: 'Giỏ hàng', url: '/cart' },
  { text: 'Đăng ký', url: '/register' },
  { text: 'Đặng nhập', url: '/login' },
];
const page = usePage();
const socialLinks = [
  { name: 'Facebook', icon: '/assets/images/social_icons/facebook.svg', url: page.props.settings.facebook_link },
  { name: 'X', icon: '/assets/images/social_icons/x.svg', url: page.props.settings.x_link },
  { name: 'Instagram', icon: '/assets/images/social_icons/instagram.svg', url: page.props.settings.instagram_link },
  { name: 'YouTube', icon: '/assets/images/social_icons/youtube.svg', url: page.props.settings.youtube_link },
];

const dynamicPages = computed(() => page.props.footer_pages || []);
</script>