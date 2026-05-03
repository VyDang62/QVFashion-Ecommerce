<script setup>
import { reactive, watch, ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/customer/CustomerLayout.vue';
import ShopProductCard from '@/components/customer/Global/ShopProductCard.vue';
import pickBy from 'lodash/pickBy';
import ShopFilterDropdown from '@/components/customer/Global/ShopFilterDropdown.vue';
import ShopSortDropdown from '@/components/customer/Global/ShopSortDropdown.vue';
import AppPaginate from '@/components/customer/Global/AppPaginate.vue';
import { useFormatter } from '@/composables/useFormatter';
import { onMounted } from 'vue';
const props = defineProps({
    products: Object,
    filters: Object,
    brands: Array,
    attributes: Array,
    priceOptions: Array,
    sortOptions: Array,
});
const page = usePage();
const getInitialFilters = () => {
    const f = props.filters || {};
    const menuData = page.props.menuCategories?.data || {}; 
    
    let urlGenders = (f.gender || []).map(String);
    let urlTypes = (f.product_types || []).map(String);
    let urlCats = (f.categories || []).map(String);

    let finalGenders = [...urlGenders];
    let finalTypes = [];
    let finalCats = [];

    const hasGenderInUrl = urlGenders.length > 0;
    const isFilteringCats = urlCats.length > 0;
    const isFilteringTypes = urlTypes.length > 0;

    Object.keys(menuData).forEach(gKey => {
        const currentGender = String(gKey);
        const productTypes = menuData[gKey];

        const genderHasMatch = productTypes.some(t => {
            if (urlTypes.includes(String(t.id))) return true;
            return t.categories.some(p => 
                urlCats.includes(String(p.id)) || p.children?.some(c => urlCats.includes(String(c.id)))
            );
        });

        if (hasGenderInUrl) {
            if (!urlGenders.includes(currentGender)) return;
        } else {
            if (!(isFilteringTypes || isFilteringCats) || !genderHasMatch) return;
        }

        if (!finalGenders.includes(currentGender)) finalGenders.push(currentGender);

        productTypes.forEach(t => {
            const typeId = String(t.id);
            const typeKey = `${currentGender}_${typeId}`;
            
            const hasChildMatch = t.categories.some(p => 
                urlCats.includes(String(p.id)) || p.children?.some(c => urlCats.includes(String(c.id)))
            );

            const shouldSelectType = urlTypes.includes(typeId) || 
                                     (hasGenderInUrl && !isFilteringTypes && !isFilteringCats) || 
                                     hasChildMatch;

            if (shouldSelectType) {
                finalTypes.push(typeKey);

                t.categories.forEach(p => {
                    const pId = String(p.id);
                    const pKey = `${currentGender}_${pId}`;
                    const hasSubChildMatch = p.children?.some(c => urlCats.includes(String(c.id)));

                    const shouldSelectParent = urlCats.includes(pId) || hasSubChildMatch || (shouldSelectType && !isFilteringCats);

                    if (shouldSelectParent) {
                        finalCats.push(pKey);

                        const isBroadSelecting = urlCats.includes(pId) || (shouldSelectType && !isFilteringCats);
                        
                        p.children?.forEach(c => {
                            const cId = String(c.id);
                            if (isBroadSelecting || urlCats.includes(cId)) {
                                finalCats.push(cId);
                            }
                        });
                    }
                });
            }
        });
    });

    return {
        gender: [...new Set(finalGenders)],
        product_types: finalTypes,
        categories: finalCats,
        brands: f.brands || [],
        attribute_values: f.attribute_values || [],
        prices: f.prices || [],
        sort: f.sort || 'latest',
        search: f.search || '',
        is_flash_sale: f.is_flash_sale === 'true' || f.is_flash_sale === true,
    };
};
const params = reactive(getInitialFilters());

watch(params, () => {
    const cleanParams = { ...params };

    cleanParams.product_types = params.product_types.map(val => val.includes('_') ? val.split('_')[1] : val);
    cleanParams.categories = params.categories.map(val => val.includes('_') ? val.split('_')[1] : val);

    router.get(route('shop.index'), pickBy(cleanParams), {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, { deep: true });
const expandedGenders = ref([...params.gender]);
const expandedTypes = ref([...params.product_types]);
const expandedParents = ref([...params.categories]);

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
    if (isChecked) {
        if (!params.gender.includes(gender)) params.gender.push(gender);
        if (!expandedGenders.value.includes(gender)) expandedGenders.value.push(gender);
    } else {
        params.gender = params.gender.filter(g => g !== gender);
        expandedGenders.value = expandedGenders.value.filter(g => g !== gender);
    }

    productTypes.forEach(type => {
        const typeKey = `${gender}_${type.id}`;
        
        if (isChecked) {
            if (!params.product_types.includes(typeKey)) params.product_types.push(typeKey);
        } else {
            params.product_types = params.product_types.filter(id => id !== typeKey);
        }

        type.categories.forEach(parent => {
            const parentKey = `${gender}_${parent.id}`;

            if (isChecked) {
                if (!params.categories.includes(parentKey)) params.categories.push(parentKey);
            } else {
                params.categories = params.categories.filter(id => id !== parentKey);
            }

            parent.children?.forEach(child => {
                const childId = String(child.id); 
                if (isChecked) {
                    if (!params.categories.includes(childId)) params.categories.push(childId);
                } else {
                    params.categories = params.categories.filter(id => id !== childId);
                }
            });
        });
    });
};

//Xử lý khi tích vào Product Type
const handleProductTypeChange = (gender, type, isChecked) => {
    const typeKey = `${gender}_${type.id}`;
    
    if (isChecked && !params.gender.includes(gender)) {
        params.gender.push(gender);
    }

    if (isChecked) {
        if (!params.gender.includes(gender)) params.gender.push(gender);
        if (!params.product_types.includes(typeKey)) params.product_types.push(typeKey);
        if (!expandedTypes.value.includes(typeKey)) {
            expandedTypes.value.push(typeKey);
        }
    } else {
        params.product_types = params.product_types.filter(id => id !== typeKey);
    }
    type.categories.forEach(parent => {
        const parentKey = `${gender}_${parent.id}`;
        if (isChecked) {
            if (!params.categories.includes(parentKey)) params.categories.push(parentKey);
        } else {
            params.categories = params.categories.filter(id => id !== parentKey);
        }
        
        parent.children?.forEach(child => {
            const childId = String(child.id);
            if (isChecked) {
                if (!params.categories.includes(childId)) params.categories.push(childId);
            } else {
                params.categories = params.categories.filter(id => id !== childId);
            }
        });
    });
};

//Xử lý khi tích vào Category Cha
const handleParentCategoryChange = (gender, parent, isChecked) => {
    const parentKey = `${gender}_${parent.id}`;
    
    if (isChecked) {
        if (!params.categories.includes(parentKey)) params.categories.push(parentKey);
        if (!expandedParents.value.includes(parentKey)) {
            expandedParents.value.push(parentKey);
        }
    } else {
        params.categories = params.categories.filter(id => id !== parentKey);
    }

    parent.children?.forEach(child => {
        const childId = String(child.id);
        if (isChecked) {
            if (!params.categories.includes(childId)) params.categories.push(childId);
        } else {
            params.categories = params.categories.filter(id => id !== childId);
        }
    });
};

const resetFilters = () => {
    params.gender.length = 0;
    params.product_types.length = 0;
    params.categories.length = 0;
    params.brands.length = 0;
    params.attribute_values.length = 0;
    params.prices.length = 0;
    params.sort = 'latest';
    params.search = '';
    params.is_flash_sale = false;

    expandedGenders.value = [];
    expandedTypes.value = [];
    expandedParents.value = [];
};

const isFiltering = computed(() => {
   return params.gender.length > 0 || params.product_types.length > 0 || 
          params.categories.length > 0 || params.brands.length > 0 || 
          params.attribute_values.length > 0 || params.prices.length > 0 || 
          params.is_flash_sale === true;
});
</script>

<template>
    <CustomerLayout>
        <Head title="Sản phẩm" />
        <div class="bg-white min-h-screen border-t border-gray-100">
            <div class="container mx-auto px-2 py-6">
                <div class="flex flex-col md:flex-row gap-4 lg:gap-12 items-start">
                    
                    <aside class="w-1/4 md:w-64 flex-shrink-0 space-y-10">
                        <div>
                            <div class="pb-2 mb-6 border-b border-gray-300">
                                <h3 class="text-[14px] font-black uppercase tracking-[0.2em]">Ưu đãi</h3>
                            </div>
                            <div class="space-y-3">
                                <label class="flex items-center cursor-pointer group">
                                    <input 
                                        type="checkbox" 
                                        v-model="params.is_flash_sale"
                                        class="form-checkbox custom-checkbox"
                                    />
                                    <span class="ml-3 text-[14px] font-bold uppercase group-hover:text-primary transition-colors flex items-center gap-2">
                                        Đang Flash Sale
                                    </span>
                                </label>
                            </div>
                        </div>
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
                                                    :checked="params.product_types.includes(gender + '_' + type.id)"
                                                    @change="handleProductTypeChange(gender, type, $event.target.checked)"
                                                    class="form-checkbox custom-checkbox"/>
                                                <span class="ml-3 text-[14px] font-bold group-hover:text-primary">
                                                    {{type.type_name}}
                                                </span>
                                            </label>
                                            <button type="button" @click.stop.prevent="toggleUI(expandedTypes, gender + '_' + type.id)" class="p-1 text-gray-400">
                                                <i :class="['fas text-[8px] transition-transform', isExpanded(expandedTypes, gender + '_' + type.id) ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                            </button>
                                        </div>

                                        <div v-show="isExpanded(expandedTypes, gender + '_' + type.id)" class="ml-4 pl-4 border-l border-gray-300 space-y-3">
                                            <div v-for="parent in type.categories" :key="parent.id">
                                                <div class="flex items-center justify-between">
                                                    <label class="flex items-center cursor-pointer flex-1">
                                                        <input type="checkbox" 
                                                            :checked="params.categories.includes(gender + '_' + parent.id)"
                                                            @change="handleParentCategoryChange(gender, parent, $event.target.checked)"
                                                            class="form-checkbox custom-checkbox scale-90"/>
                                                        <span class="ml-2 text-[14px] font-bold">{{parent.category_name}}</span>
                                                    </label>
                                                    <button v-if="parent.children?.length" type="button" 
                                                            @click.stop.prevent="toggleUI(expandedParents, gender + '_' + parent.id)" class="p-1 text-gray-300">
                                                        <i :class="['fas text-[8px] transition-transform', isExpanded(expandedParents, gender + '_' + parent.id) ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                                    </button>
                                                </div>

                                                <div v-show="isExpanded(expandedParents, gender + '_' + parent.id)" class="ml-4 pl-4 border-l border-gray-300 mt-2 space-y-3">
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
                                v-for="attr in attributes"
                                :key="attr.id"
                                :label="attr.attribute_name"
                                :options="attr.values"
                                v-model="params.attribute_values"
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