<script setup>
import { reactive, watch, ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import ShopProductCard from '@/components/customer/Global/ShopProductCard.vue';
import pickBy from 'lodash/pickBy';
import ShopFilterDropdown from '@/components/customer/Global/ShopFilterDropdown.vue';
import ShopSortDropdown from '@/components/customer/Global/ShopSortDropdown.vue';
import AppPaginate from '@/components/customer/Global/AppPaginate.vue';
import { useFormatter } from '@/composables/useFormatter';

const props = defineProps({
    products: Object,
    filters: Object,
    brands: Array,
    sizes: Array,
    colors: Array,
    priceOptions: Array,
    sortOptions: Array,
});

const { formatPrice } = useFormatter();

const params = reactive({
    gender: props.filters.gender || [], 
    product_types: (props.filters.product_types || []).map(String), 
    categories: (props.filters.categories || []).map(String),
    brands: props.filters.brands || [],
    sizes: props.filters.sizes || [],
    colors: props.filters.colors || [],
    prices: props.filters.prices || [],
    sort: props.filters.sort || 'latest',
    search: props.filters.search || '',
});

watch(params, () => {
    router.get(route('shop.index'), pickBy(params), {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, { deep: true });

const expandedGenders = ref([...params.gender].map(String));
const expandedTypes = ref([...params.product_types].map(String));
const expandedParents = ref([...params.categories].map(String));

const isExpanded = (list, item) => {
    return Array.isArray(list) && list.includes(String(item));
};

const toggleUI = (list, item) => {
    if (!list) return; 
    
    const val = String(item);
    const index = list.indexOf(val);
    
    if (index > -1) {
        list.splice(index, 1);
    } else {
        list.push(val);
    }
};

//Xử lý khi tích vào Gender
const handleGenderChange = (gender, productTypes, isChecked) => {
    //Cập nhật mảng gender
    if (isChecked) {
        if (!params.gender.includes(gender)) params.gender.push(gender);
        if (!expandedGenders.value.includes(gender)) expandedGenders.value.push(gender);
    } else {
        params.gender = params.gender.filter(g => g !== gender);
    }

    //Lấy toàn bộ ID con thuộc Gender này
    let typeIds = [];
    let catIds = [];
    productTypes.forEach(type => {
        typeIds.push(String(type.id));
        type.categories.forEach(parent => {
            catIds.push(String(parent.id));
            parent.children?.forEach(child => catIds.push(String(child.id)));
        });
    });

    if (isChecked) {
        //Thêm toàn bộ con vào params
        params.product_types = [...new Set([...params.product_types, ...typeIds])];
        params.categories = [...new Set([...params.categories, ...catIds])];
    } else {
        //Dọn sạch con thuộc gender này khỏi params
        params.product_types = params.product_types.filter(id => !typeIds.includes(id));
        params.categories = params.categories.filter(id => !catIds.includes(id));
    }
};

//Xử lý khi tích vào Product Type
const handleProductTypeChange = (gender, type, isChecked) => {
    const typeId = String(type.id);

    //Tự động tích Gender cha nếu chưa có
    if (isChecked && !params.gender.includes(gender)) {
        params.gender.push(gender);
    }

    if (isChecked) {
        if (!params.product_types.includes(typeId)) params.product_types.push(typeId);
    } else {
        params.product_types = params.product_types.filter(id => id !== typeId);
    }

    //Lấy ID của tất cả Category thuộc Type này
    let catIds = [];
    type.categories.forEach(parent => {
        catIds.push(String(parent.id));
        parent.children?.forEach(child => catIds.push(String(child.id)));
    });

    if (isChecked) {
        params.categories = [...new Set([...params.categories, ...catIds])];
    } else {
        params.categories = params.categories.filter(id => !catIds.includes(id));
    }
};

//Xử lý khi tích vào Category Cha
const handleParentCategoryChange = (parent, isChecked) => {
    const parentId = String(parent.id);
    const childIds = parent.children?.map(c => String(c.id)) || [];

    if (isChecked) {
        if (!params.categories.includes(parentId)) params.categories.push(parentId);
        params.categories = [...new Set([...params.categories, ...childIds])];
    } else {
        params.categories = params.categories.filter(id => id !== parentId && !childIds.includes(id));
    }
};

const resetFilters = () => {
    params.gender = [];
    params.product_types = [];
    params.categories = [];
    params.brands = [];
    params.sizes = [];
    params.colors = [];
    params.prices = [];
    params.sort = 'latest';
};

const isFiltering = computed(() => {
   return params.gender.length > 0 || params.product_types.length > 0 || 
          params.categories.length > 0 || params.brands.length > 0 || 
          params.prices.length > 0;
});
</script>

<template>
    <CustomerLayout>
        <Head title="Sản phẩm" />
        <div class="bg-white min-h-screen border-t border-gray-100">
            <div class="container mx-auto px-2 py-6">
                <div class="flex flex-col md:flex-row gap-4 lg:gap-12 items-start">
                    
                    <aside class="w-1/4 md:w-64 flex-shrink-0 space-y-10">
                        <div class="pb-2 mb-6 border-b border-gray-300">
                            <h3 class="text-[14px] font-black uppercase tracking-[0.2em]">Danh mục</h3>
                        </div>

                        <div class="space-y-6">
                            <div v-for="(productTypes, gender) in $page.props.menuCategories.data" :key="gender" class="space-y-3"> 
                                <div class="flex items-center justify-between group">
                                    <label class="flex items-center cursor-pointer flex-1">
                                        <input type="checkbox" :checked="params.gender.includes(gender)"
                                            @change="handleGenderChange(gender, productTypes, $event.target.checked)"
                                            class="form-checkbox custom-checkbox"/>
                                        <span class="ml-3 text-[14px] font-bold uppercase group-hover:text-primary transition-colors">
                                            {{$page.props.menuCategories.labels[gender]}}
                                        </span>
                                    </label>
                                    <button type="button" @click.stop.prevent="toggleUI(expandedGenders, gender)" class="text-gray-400 hover:text-black">
                                        <i :class="['mr-1 fas text-[8px] transition-transform', isExpanded(expandedGenders, gender) ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                    </button>
                                </div>

                                <div v-show="isExpanded(expandedGenders, gender)" class="ml-4 pl-4 border-l border-gray-300 space-y-4">
                                    <div v-for="type in productTypes" :key="type.id" class="space-y-2">
                                        <div class="flex items-center justify-between group">
                                            <label class="flex items-center cursor-pointer flex-1">
                                                <input type="checkbox" 
                                                    :checked="params.gender.includes(gender) && params.product_types.includes(String(type.id))"
                                                    @change="handleProductTypeChange(gender, type, $event.target.checked)"
                                                    class="form-checkbox custom-checkbox"/>
                                                <span class="ml-3 text-[14px] font-bold group-hover:text-primary">
                                                    {{type.type_name}}
                                                </span>
                                            </label>
                                            <button type="button" @click.stop.prevent="toggleUI(expandedTypes, type.id)" class="p-1 text-gray-400">
                                                <i :class="['fas text-[8px] transition-transform', isExpanded(expandedTypes, type.id) ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                            </button>
                                        </div>

                                        <div v-show="isExpanded(expandedTypes, type.id)" class="ml-4 pl-4 border-l border-gray-300 space-y-3">
                                            <div v-for="parent in type.categories" :key="parent.id">
                                                <div class="flex items-center justify-between">
                                                    <label class="flex items-center cursor-pointer flex-1">
                                                        <input type="checkbox" 
                                                            :checked="params.gender.includes(gender) && params.categories.includes(String(parent.id))"
                                                            @change="handleParentCategoryChange(parent, $event.target.checked)"
                                                            class="form-checkbox custom-checkbox scale-90"/>
                                                        <span class="ml-2 text-[14px] font-bold">{{parent.category_name}}</span>
                                                    </label>
                                                    <button v-if="parent.children?.length" type="button" 
                                                            @click.stop.prevent="toggleUI(expandedParents, parent.id)" class="p-1 text-gray-300">
                                                        <i :class="['fas text-[8px] transition-transform', isExpanded(expandedParents, parent.id) ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                                    </button>
                                                </div>

                                                <div v-show="isExpanded(expandedParents, parent.id)" class="ml-4 pl-4 border-l border-gray-300 mt-2 space-y-3">
                                                    <label v-for="child in parent.children" :key="child.id" class="flex items-center cursor-pointer group">
                                                        <input type="checkbox" :value="String(child.id)" v-model="params.categories"
                                                            class="form-checkbox custom-checkbox scale-75">
                                                        <span class="ml-2 text-[14px] font-bold group-hover:text-primary">{{ child.category_name }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <main class="flex-1 w-full">
                        <div class="flex flex-wrap items-center gap-x-12 pb-2 mb-8 border-b border-black border-gray-300">
                            <span class="text-[14px] font-semibold tracking-[0.1em]">Lọc theo:</span>
                            <ShopFilterDropdown
                                label="Thương hiệu"
                                :options="brands"
                                v-model="params.brands"
                            />
                            <ShopFilterDropdown
                                label="Size"
                                :options="sizes"
                                v-model="params.sizes"
                            />
                            <ShopFilterDropdown
                                label="Màu sắc"
                                :options="colors"
                                v-model="params.colors"
                            />
                            <ShopFilterDropdown
                                label="Giá"
                                :options="priceOptions"
                                v-model="params.prices"
                            />
                            <ShopSortDropdown
                                :options="sortOptions"
                                v-model="params.sort"
                            />
                            <transition name="fade">
                                <button
                                    v-if="isFiltering"
                                    @click="resetFilters"
                                    class="text-[12px] font-bold text-red-500 hover:text-red-700 flex items-center transition-all"
                                >
                                    XÓA BỘ LỌC
                                </button>
                            </transition>
                        </div>
                        <div v-if="params.search" class="mb-8 flex items-center justify-between p-4 rounded-xl">
                            <div class="flex items-baseline gap-2">
                                <span class="text-black text-m font-semibold">Kết quả tìm kiếm cho:</span>
                                <span class="text-black text-m font-black ">"{{ params.search }}"</span>
                                <span class="ml-2 text-gray-800 text-s">({{ products.total }} kết quả)</span>
                            </div>
                            <button
                                @click="params.search = ''"
                                class="text-xs font-bold text-primary hover:text-red-500 transition-colors flex items-center gap-1"
                            >
                                Xóa tìm kiếm
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 gap-x-2 sm:gap-x-0 lg:gap-x-4 gap-y-8 lg:gap-y-12">
                            <ShopProductCard
                                v-for="product in products.data"
                                :key="product.id"
                                :product="product"
                            />
                        </div>
                        <AppPaginate :links="products.links"/>
                    </main>

                </div>
            </div>
        </div>
    </CustomerLayout>
</template>