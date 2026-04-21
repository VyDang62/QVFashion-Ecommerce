<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import { useFormatter } from '@/composables/useFormatter';
import Badge from '@/components/admin/ui/Badge.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    product: Object 
});
const activeImage = ref(
    props.product.images.find(img => img.is_primary)?.image_path || 
    props.product.images[0]?.image_path || 
    'defaults/product-placeholder.jpg'
);

const setMainImage = (path) => {
    activeImage.value = path;
};

const { formatPrice, formatDate } = useFormatter();
const totalStock = computed(() => {
    return props.product.variants.reduce((sum, v) => sum + v.stock_quantity, 0);
});
const displayPrice = computed(() => {
    if (props.product.variants.length === 0) return 0;
    const prices = props.product.variants.map(v => parseFloat(v.price));
    return Math.min(...prices);
});
</script>

<template>
    <Head :title="'Chi tiết: ' + product.product_name"/>
    <AdminLayout>
        <PageBreadcrumb :pageTitle="product.product_name" parentName="Sản phẩm" :parentRoute="route('admin.products.index') "/>

        <div class="mb-6 flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ product.product_name }}</h2>
                    <div class="flex gap-2 mt-1">
                        <span v-if="product.deleted_at" class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold uppercase">Đang trong thùng rác</span>
                        <span v-if="!product.is_active" class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-bold uppercase">Tạm ẩn</span>
                        <span v-if="product.is_featured" class="text-[10px] bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full font-bold uppercase">Nổi bật</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <Link v-if="can('products.edit')" :href="route('admin.products.edit', product.id)" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                    CHỈNH SỬA
                </Link>
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-8">
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-xl relative group">
                    <img :src="'/storage/' + activeImage" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                    <div v-if="product.rating_avg > 0" class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full shadow-sm flex items-center gap-1">
                        <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <span class="text-sm font-bold text-gray-900">{{ product.rating_avg }} ({{product.approved_ratings_count}})</span>
                    </div>
                </div>
                <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar">
                    <div 
                        v-for="img in product.images" 
                        :key="img.id"
                        @click="setMainImage(img.image_path)"
                        class="w-20 h-20 rounded-xl overflow-hidden border-2 flex-shrink-0 cursor-pointer transition-all"
                        :class="activeImage === img.image_path ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-100 hover:border-blue-300'"
                    >
                        <img :src="'/storage/' + img.image_path" class="w-full h-full object-cover" />
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 text-blue-600 font-medium text-sm mb-2">
                                {{ product.category?.parent?.category_name || 'Danh mục' }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="2.5"/></svg>
                                {{ product.category?.category_name }}
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 leading-tight">{{ product.product_name }}</h1>
                            <div class="flex items-center gap-4 mt-2">
                                <p class="text-gray-500 font-medium text-sm">ID: <span class="text-gray-700">#{{ product.id }}</span></p>
                                <p class="text-gray-500 font-medium text-sm">Lượt xem: <span class="text-gray-700">{{ product.view_count }}</span></p>
                                <p class="text-gray-500 font-medium text-sm">Đã bán: <span class="text-gray-700">{{ product.order_count }}</span></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-600 font-semibold mb-1">Tổng tồn kho</p>
                            <span class="text-xl font-bold" :class="totalStock > 0 ? 'text-gray-900' : 'text-red-500'">
                                {{ totalStock }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <div class="flex-1">
                            <p class="text-xs text-gray-600 font-bold mb-1">Giá từ</p>
                            <p class="text-xl font-bold text-red-600">{{ formatPrice(displayPrice) }}</p>
                        </div>
                        <div class="w-px h-10 bg-gray-200"></div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-600 font-bold mb-1">Thương hiệu</p>
                            <p class="text-xl font-bold text-gray-800">{{ product.brand?.brand_name || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-50 bg-gray-50/30 flex justify-between items-center">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2 text-lg">
                            Danh sách biến thể
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 text-[10px] font-bold text-gray-400 uppercase">
                                <tr>
                                    <th class="px-6 py-4 text-xs text-gray-600">SKU</th>
                                    <th class="px-6 py-4 text-xs text-gray-600">Thuộc tính</th>
                                    <th class="px-6 py-4 text-right text-xs text-gray-600">Giá bán</th>
                                    <th class="px-6 py-4 text-center text-xs text-gray-600">Tồn kho</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="variant in product.variants" :key="variant.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-mono text-xs font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded w-fit">
                                            {{ variant.sku }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge 
                                                v-for="av in variant.attribute_values" 
                                                :key="av.id" 
                                                variant="light" 
                                                size="sm" 
                                                color="primary"
                                                class="flex items-center gap-1"
                                            >
                                                <span v-if="av.hex_code" 
                                                    class="w-2 h-2 rounded-full border border-gray-200" 
                                                    :style="{ backgroundColor: av.hex_code }">
                                                </span>
                                                
                                                <span class="font-semibold text-xs">
                                                    {{ av.attribute.attribute_name }}: {{ av.value }}
                                                </span>
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900 text-right">{{ formatPrice(variant.price) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <Badge :color="variant.stock_quantity > variant.low_stock_threshold ? 'success' : 'error'" size="sm" variant="solid">{{ variant.stock_quantity }} cái</Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2 text-lg">Mô tả sản phẩm</h3>
                    <div class="text-gray-600 leading-relaxed whitespace-pre-line text-sm">
                        {{ product.product_description || 'Sản phẩm này hiện chưa có mô tả chi tiết.' }}
                    </div>
                </div>

                <div v-if="product.meta_title || product.meta_description" class="bg-gray-900 p-6 rounded-3xl shadow-sm text-white">
                    <h3 class="font-bold text-gray-400 mb-4 text-[10px] uppercase tracking-widest">Xem trước hiển thị tìm kiếm (SEO)</h3>
                    <p class="text-blue-400 text-lg font-medium hover:underline cursor-pointer">{{ product.meta_title || product.product_name }}</p>
                    <p class="text-emerald-500 text-xs mb-1">qvfashion.com › sản-phẩm › {{ product.slug }}</p>
                    <p class="text-gray-400 text-sm line-clamp-2">{{ product.meta_description || product.product_description }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>