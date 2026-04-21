<script setup>
import {ref,computed} from 'vue';
import {Link, Head, router, useForm} from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import { useFormatter } from '@/composables/useFormatter';
import ProductCard from '@/components/customer/Global/ProductCard.vue';
import {route} from 'ziggy-js';
const {formatDateTime} = useFormatter();

const props = defineProps({
    product: Object,
    attributes: Object,
    relatedProducts: Array,
    seo: Object,
    isFavorited: Boolean,
    ratingsCount: Number,
    averageRating: Number,
});

const activeTab = ref('description');
const quantity = ref(1);

const mainImage = ref(props.product.images[0]?.image_path || '/assets/images/placeholder.jpg');
const changeImage = (path) => {
    mainImage.value = path;
};

const updateQuantity = (amount) => {
    const newQuantity = quantity.value + amount;
    const maxStock = displayStock.value || 999;
    if(newQuantity >= 1 && newQuantity <= maxStock){
        quantity.value = newQuantity;
    }
}

const {formatPrice} = useFormatter();

const getStorageUrl = (path) => {
  return `/storage/${path}`;
};

const selectedAttributes = ref({});

const currentVariant = computed(() => {
    if(Object.keys(selectedAttributes.value).length < Object.keys(props.attributes).length){
        return null;
    }
    
    return props.product.variants.find(variant => {
        return variant.attribute_values.every(av => 
            selectedAttributes.value[av.attribute.attribute_name] === av.value
        );
    });
});

const displayPrice = computed(() => {
    if(currentVariant.value){
        return currentVariant.value.sale_price || currentVariant.value.price;
    }

    const allPrices = props.product.variants.map(v => v.sale_price || v.price);
    return Math.min(...allPrices);
});

const displayOldPrice = computed(() => {
    if (currentVariant.value && currentVariant.value.sale_price) {
        return currentVariant.value.price;
    }
    return null;
});
const discountPercent = computed(() => {
    if (currentVariant.value) {
        return currentVariant.value.discount || 0;
    }
    
    const discounts = props.product.variants.map(v => v.discount || 0);
    return Math.max(...discounts);
});

const displayStock = computed(() => currentVariant.value ? currentVariant.value.stock_quantity : null);
const displaySKU = computed(() => currentVariant.value ? currentVariant.value.sku : null);

const scrollToReviews = () => {
    activeTab.value = 'reviews';

    setTimeout(() => {
        const element = document.getElementById('product-reviews-section');
        if(element){
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }, 100);
};

//Cart
const isProcessingAddToCart = ref(null);
const addToCart = () => {
    if (!currentVariant.value) {
        return;
    }

    router.post(route('cart.add'), {
        product_id: props.product.id,
        product_variant_id: currentVariant.value.id,
        quantity: quantity.value
    }, {
        preserveScroll: true,
        onStart: () => isProcessingWishlist.value = true,
        onFinish: () => isProcessingWishlist.value = false,
        onSuccess: () => {
            window.dispatchEvent(new CustomEvent('open-cart-dropdown'));
            quantity.value = 1;
        },
    });
};
//Wishlist
const isProcessingWishlist = ref(null);

const isInWishlist = computed(() => props.isFavorited);

const toggleWishlist = () => {
    router.post(route('wishlist.store'),{
        product_id: props.product.id
    },{
        preserveScroll: true,
        onStart: () => isProcessingWishlist.value = true,
        onFinish: () => isProcessingWishlist.value = false,
    });
}
//Form đánh giá
const reviewForm = useForm({
    product_id: props.product.id,
    score: 5,
    content: '',
});

const submitReview = () => {
    reviewForm.post(route('product.review.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset('content', 'score');
        },
    });
}
</script>
<template>
    <CustomerLayout>
        <Head>
            <title>{{seo.title}}</title>
            <meta name="description" :content="seo.description">
            <meta property="og:type" content="product">
            <meta property="og:title" :content="seo.title">
            <meta property="og:description" :content="seo.description">
            <meta property="og:image" :content="seo.image">
            <meta property="og:url" :content="seo.url">
            
            <component :is="'script'" type="application/ld+json">
                {{ JSON.stringify(seo.schema) }}
            </component>
        </Head>
        <section class="pt-6 bg-gray-50 ">
            <div class="container mx-auto px-4 ">
                <ol class="flex text-m text-gray-600">
                    <li><Link href="/" class="font-semibold hover:text-primary">Trang chủ</Link></li>
                    <li class="mx-2">&gt;</li>
                    <li><Link :href="route('shop.index')" class="font-semibold hover:text-primary">Cửa hàng</Link></li>
                    <li class="mx-2">&gt;</li>
                    <li class="font-semibold text-gray-900">{{ product.product_name }}</li>
                </ol>
            </div>
        </section>
        <section class="py-6">
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row gap-10 bg-white p-6 rounded-2xl shadow-sm">
                
                    <div class="w-full lg:w-1/2">
                        <div class="grid gap-4">
                        <div class="overflow-hidden rounded-xl bg-gray-50 border border-gray-100 h-[480px] lg:h-[600px]">
                            <transition name="fade" mode="out-in">
                            <img
                                :key="mainImage"
                                :src="getStorageUrl(mainImage)" 
                                class="w-full h-full object-contain transition-all duration-500 transform"
                                alt="Main Product Image" 
                            />
                            </transition>
                        </div>
                        <div class="grid grid-cols-5 gap-4">
                            <div 
                            v-for="(img, index) in product.images" 
                            :key="index"
                            @mouseover="changeImage(img.image_path)"
                            @click="changeImage(img.image_path)"
                            class="group relative cursor-pointer rounded-lg overflow-hidden border-2 transition-all duration-300"
                            :class="[mainImage === img.image_path ? 'border-primary shadow-md' : 'border-transparent opacity-70 hover:opacity-100']"
                            >
                            <img 
                                :src="getStorageUrl(img.image_path)" 
                                class="object-cover h-20 w-full transition-transform duration-500 group-hover:scale-110" 
                            />
                            
                            <div v-if="mainImage === img.image_path" class="absolute inset-0 bg-primary/5"></div>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/2 flex flex-col justify-between">
                        <div class="pb-8 border-b border-gray-200">
                        <h1 class="text-3xl font-extrabold mb-4 text-gray-900 uppercase tracking-tight">{{ product.product_name }}</h1>
                        
                        <div class="flex items-center mb-6 text-sm">
                            <div class="flex text-yellow-400 mr-2">
                                <i v-for="i in 5" :key="i"
                                    :class="[
                                        i <= Math.floor(averageRating) 
                                            ? 'fas fa-star' 
                                            : (i - 0.5 <= averageRating ? 'fas fa-star-half-alt' : 'far fa-star')
                                    ]"
                                ></i>
                                
                                <span v-if="ratingsCount > 0" class="ml-2 font-black text-gray-900 text-md">
                                    {{ averageRating }}
                                </span>
                            </div>
                            <span class="text-gray-500 font-medium">
                                ({{ ratingsCount }} {{ ratingsCount > 0 ? 'Đánh giá' : 'Chưa có đánh giá' }})
                            </span>
                            <a href="#reviews" 
                                @click.prevent="scrollToReviews"
                                class="ml-4 text-primary font-semibold hover:underline"
                            >Viết đánh giá</a>
                        </div>

                        <div class="space-y-3 mb-6 pb-4 text-sm border-b border-gray-100">
                            <p class="text-gray-600">Thương hiệu: <span class="font-bold text-gray-900 ml-1">{{ product.brand.brand_name }}</span></p>
                            <p class="text-gray-600">Mã sản phẩm: 
                                <span class="font-bold text-gray-900 ml-1">{{ displaySKU === null ? 'Vui lòng chọn size và màu sắc' : displaySKU }}</span></p>
                            <p class="text-gray-600">Tình trạng: 
                                <span class="ml-1 font-bold" :class="product.stock > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ displayStock === null ? 'Vui lòng chọn size và màu sắc' : displayStock + ' sản phẩm' }}
                                </span>
                            </p>
                        </div>
                        <div class="flex flex-col mb-6">
                            <div class="flex items-center gap-3">
                                <span class="text-[22px] font-black text-primary">
                                    {{ formatPrice(displayPrice) }}
                                </span>

                                <span v-if="displayOldPrice" class="text-lg text-gray-400 line-through decoration-red-500/30">
                                    {{ formatPrice(displayOldPrice) }}
                                </span>

                                <span v-if="discountPercent > 0" class="bg-red-600 text-white text-xs font-black px-2 py-1 rounded shadow-sm animate-pulse">
                                    -{{ discountPercent }}%
                                </span>
                            </div>

                            <div v-if="currentVariant?.sale_price" class="mt-2 flex items-center gap-2 text-orange-500 text-xs font-bold uppercase tracking-wider">
                                <i class="fas fa-bolt"></i>
                                <span>Giá ưu đãi đang diễn ra</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-6">
                            <div class="py-6 space-y-6">
                                <div v-for="(values, attrName) in attributes" :key="attrName">
                                    <h3 class="text-sm font-bold uppercase mb-3 text-gray-700">
                                        {{attrName}}: <span class="text-primary ml-1">{{selectedAttributes[attrName]}}</span>
                                    </h3>
                                    <div class="flex flex-wrap gap-3">
                                        <button
                                        v-for="v in values"
                                        :key="v.id"
                                        @click="selectedAttributes[attrName] = v.value"
                                        :class="[
                                            'related px-4 py-2 rounded-lg border-2 transition-all duration-200 text-sm font-medium flex items-center gap-2',
                                            selectedAttributes[attrName] === v.value
                                            ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        ]">
                                            <span
                                            v-if="v.hex_code"
                                            :style="{backgroundColor: v.hex_code}"
                                            class="w-4 h-4 rounded-full border border-gray-200">
                                        </span>
                                        {{v.value}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 mb-8">
                            <div>
                                <h3 class="text-sm font-bold uppercase mb-3 text-gray-700">
                                    <span>Số lượng:</span>
                                </h3>
                                <div class="flex items-center border border-gray-200 rounded-full p-1 bg-gray-50">
                                    <button 
                                        @click="updateQuantity(-1)"
                                        class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white transition shadow-sm disabled:opacity-30"
                                        :disabled="quantity <= 1"
                                    >
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" v-model="quantity" class="w-12 text-center bg-transparent border-none focus:ring-0 font-bold" readonly />
                                    <button 
                                        @click="updateQuantity(1)"
                                        class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white transition shadow-sm"
                                    >
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <button
                                @click="addToCart" 
                                :disabled="!currentVariant || displayStock <= 0 || isProcessingAddToCart"
                                class="flex w-[300px] gap-2 items-center justify-center bg-primary border border-primary text-white hover:bg-transparent hover:text-primary font-bold py-3 px-8 rounded-full transition-all duration-300 shadow-lg disabled:bg-gray-300 disabled:border-gray-300"
                            >
                                {{ !currentVariant ? 'CHỌN SIZE/MÀU SẮC' : displayStock <= 0 ? 'HẾT HÀNG' : 'THÊM VÀO GIỎ HÀNG' }}
                            </button>
                            <button
                                @click="toggleWishlist"
                                :disabled="isProcessingWishlist"
                                class="flex w-[300px] items-center justify-center gap-2 bg-primary border border-primary text-white hover:bg-transparent hover:text-primary font-bold py-3 px-8 rounded-full transition-all duration-300 shadow-lg disabled:bg-gray-300 disabled:border-gray-300"
                            >
                                <span>{{ isInWishlist ? 'XÓA YÊU THÍCH' : 'YÊU THÍCH' }}</span>
                            </button>
                        </div>
                        </div>
                        <div class="flex space-x-4 my-6">
                            <a href="#" class="w-4 h-4 flex items-center justify-center">
                                <img src="/assets/images/social_icons/facebook.svg" alt="Facebook"
                                    class="w-4 h-4 transition-transform transform hover:scale-110">
                            </a>
                            <a href="#" class="w-4 h-4 flex items-center justify-center">
                                <img src="/assets/images/social_icons/instagram.svg" alt="Instagram"
                                    class="w-4 h-4 transition-transform transform hover:scale-110">
                            </a>
                            <a href="#" class="w-4 h-4 flex items-center justify-center">
                                <img src="/assets/images/social_icons/pinterest.svg" alt="Pinterest"
                                    class="w-4 h-4 transition-transform transform hover:scale-110">
                            </a>
                            <a href="#" class="w-4 h-4 flex items-center justify-center">
                                <img src="/assets/images/social_icons/twitter.svg" alt="Twitter"
                                    class="w-4 h-4 transition-transform transform hover:scale-110">
                            </a>
                            <a href="#" class="w-4 h-4 flex items-center justify-center">
                                <img src="/assets/images/social_icons/viber.svg" alt="Viber"
                                    class="w-4 h-4 transition-transform transform hover:scale-110">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="product-reviews-section" class="py-12 bg-white mt-6" >
            <div class="container mx-auto px-4">
                <div class="flex space-x-8 border-b border-gray-100" role="tablist">
                    <button 
                        @click="activeTab = 'description'"
                        :class="['pb-4 text-sm font-bold uppercase tracking-wider transition-all relative', activeTab === 'description' ? 'text-primary' : 'text-gray-400 hover:text-gray-600']"
                    >
                        Mô tả sản phẩm
                        <div v-if="activeTab === 'description'" class="absolute bottom-0 left-0 w-full h-0.5 bg-primary"></div>
                    </button>
                    <button 
                        @click="activeTab = 'additional'"
                        :class="['pb-4 text-sm font-bold uppercase tracking-wider transition-all relative', activeTab === 'additional' ? 'text-primary' : 'text-gray-400 hover:text-gray-600']"
                    >
                        Thông tin bổ sung
                        <div v-if="activeTab === 'additional'" class="absolute bottom-0 left-0 w-full h-0.5 bg-primary"></div>
                    </button>
                    <button 
                        @click="activeTab = 'reviews'"
                        :class="['pb-4 text-sm font-bold uppercase tracking-wider transition-all relative', activeTab === 'reviews' ? 'text-primary' : 'text-gray-400 hover:text-gray-600']"
                    >
                        Đánh giá ({{ product.ratings ? product.ratings.length : 0 }})
                        <div v-if="activeTab === 'reviews'" class="absolute bottom-0 left-0 w-full h-0.5 bg-primary"></div>
                    </button>
                </div>

                <div class="mt-10">
                    <div v-if="activeTab === 'description'" class="animate-fadeIn">
                        <div class="prose max-w-none text-gray-600 leading-relaxed">
                            <h3 class="text-xl font-semibold mb-4 text-gray-900">Chi tiết sản phẩm {{ product.product_name }}</h3>
                            <p v-html="product.product_description"></p>
                        </div>
                    </div>

                    <div v-if="activeTab === 'additional'" class="animate-fadeIn">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div v-for="(values, attrName) in attributes" :key="attrName" class="border-b border-gray-50 pb-4">
                                <h4 class="font-bold text-gray-900 uppercase text-xs mb-2">{{ attrName }}</h4>
                                <div class="flex gap-2">
                                    <span v-for="v in values" :key="v.id" class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded">
                                        {{ v.value }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'reviews'" class="animate-fadeIn">
                        <div class="flex flex-col lg:flex-row gap-12">
                            <div class="w-full lg:w-2/3 min-w-0">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-gray-900">
                                        Đánh giá từ khách hàng ({{ product.ratings ? product.ratings.length : 0 }})
                                    </h3>
                                    <div v-if="product.ratings?.length > 0" class="flex items-center gap-2 bg-yellow-50 px-3 py-1 rounded-full">
                                        <span class="text-yellow-600 font-black text-lg">{{ averageRating }}</span>
                                        <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    </div>
                                </div>
                                
                                <div v-if="product.ratings && product.ratings.length > 0" class="divide-y divide-gray-100">
                                    <div v-for="rating in product.ratings" :key="rating.id" class="py-6">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-3 min-w-0"> 
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex-shrink-0 flex items-center justify-center text-primary font-bold">
                                                    {{ rating.user.full_name.charAt(0) }}
                                                </div>
                                                <div class="min-w-0"> 
                                                    <span class="block font-bold text-gray-900 uppercase text-xs truncate">
                                                        {{ rating.user.full_name }}
                                                    </span>
                                                    <div class="flex text-yellow-400 text-[10px]">
                                                        <i v-for="star in 5" :key="star" :class="[star <= rating.score ? 'fas fa-star' : 'far fa-star']"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-xs text-gray-400 flex-shrink-0 ml-2">
                                                {{ formatDateTime(rating.created_at) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-gray-600 text-sm ml-0 sm:ml-13 whitespace-normal break-words leading-relaxed text-justify">
                                            {{ rating.content }}
                                        </p>
                                    </div>
                                </div>

                                <div v-else class="bg-gray-50 p-8 rounded-xl text-center">
                                    <p class="text-gray-500 italic">Sản phẩm này chưa có đánh giá. Hãy là người đầu tiên!</p>
                                </div>
                            </div>

                            <div class="w-full lg:w-1/3 bg-gray-50 p-6 rounded-2xl h-fit">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Viết đánh giá của bạn</h3>
                                <form @submit.prevent="submitReview" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Xếp hạng</label>
                                        <div class="flex gap-2 bg-white/5 p-3 rounded-xl border border-white/10">
                                            <button 
                                                v-for="star in 5" 
                                                :key="star" 
                                                type="button"
                                                @click="reviewForm.score = star"
                                                class="text-xl transition-all duration-200 focus:outline-none"
                                                :class="star <= reviewForm.score ? 'text-yellow-400 scale-110' : 'text-gray-600 hover:text-gray-500'"
                                            >
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Nội dung</label>
                                        <textarea v-model="reviewForm.content" required rows="4" placeholder="Cảm nhận của bạn về sản phẩm..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-primary focus:border-primary"></textarea>
                                        <p v-if="reviewForm.errors.content" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.content }}</p>
                                    </div>
                                    <button 
                                        type="submit" 
                                        :disabled="reviewForm.processing"
                                        class="w-full bg-primary text-white font-black py-4 rounded-full hover:bg-white hover:text-primary transition-all duration-300 shadow-lg shadow-primary/20 disabled:bg-gray-700 disabled:shadow-none flex items-center justify-center gap-2 uppercase text-xs tracking-widest"
                                    > 
                                        <i v-if="reviewForm.processing" class="fas fa-spinner animate-spin"></i>
                                        <span>{{ reviewForm.processing ? 'Đang gửi...' : 'Gửi đánh giá ngay' }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-10">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold mb-8">Sản phẩm liên quan</h2>
                <div class="flex flex-wrap -mx-4">
                    <ProductCard 
                    v-for="product in relatedProducts" 
                    :key="'related-' + product.id" 
                    :product="product" 
                    />
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

.fade-enter-to,
.fade-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>