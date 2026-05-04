<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import DeleteAction from '@/components/admin/actions/DeleteAction.vue';
import ConfirmAction from '@/components/admin/actions/ConfirmAction.vue';
import { useFormatter} from '@/composables/useFormatter';
import Badge from '@/components/admin/ui/Badge.vue';
import { usePermission } from '@/composables/usePermission';
const {can} = usePermission();
const props = defineProps({
    products: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '', perPage: 10, status: 'active' }) }
});

const status = ref(props.filters.status || 'active');
const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);

const tableHeaders = ['ID','Sản phẩm', 'Thương hiệu / Danh mục', 'Giá bán', 'Tồn kho', 'Trạng thái', 'Thao tác'];

watch([searchTerm, perPage, status], debounce(([newSearch, newPerPage, newStatus]) => {
    router.get(route('admin.products.index'), { 
        search: newSearch, 
        perPage: newPerPage, 
        status: newStatus 
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 500));


const {formatPrice} = useFormatter();
const getDisplayPrice = (product) => {
    if (!product.variants || product.variants.length === 0) return 'Chưa có giá';
    const prices = product.variants.map(v => Number(v.price));
    const minPrice = Math.min(...prices);
    const maxPrice = Math.max(...prices);
    
    return minPrice === maxPrice 
        ? formatPrice(minPrice) 
        : `${formatPrice(minPrice)} - ${formatPrice(maxPrice)}`;
};

const getTotalStock = (product) => {
    return product.variants?.reduce((total, v) => total + (Number(v.stock_quantity) || 0), 0) ?? 0;
};

const getPrimaryImage = (product) => {
    const primary = product.images?.find(img => img.is_primary);
    return primary ? `/storage/${primary.image_path}` : '/images/placeholder.jpg';
};

const handleRestore = (id) => {
    router.post(route('admin.products.restore', id), {}, {
        preserveScroll: true,
    });
};

const handleForceDelete = (id) => {
    router.delete(route('admin.products.forcedelete', id), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Quản lý sản phẩm" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Sản phẩm" />
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex gap-6">
                    <button 
                        @click="status = 'active'" 
                        :class="status === 'active' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-4 px-1 border-b-2 font-bold text-sm transition-all"
                    >
                        Đang kinh doanh
                    </button>
                    <button 
                        @click="status = 'trash'" 
                        :class="status === 'trash' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-4 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2"
                    >
                        Ngừng kinh doanh
                    </button>
                </div>
                <Link v-if="status === 'active' && can('products.create')" :href="route('admin.products.create')" 
                      class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                    THÊM SẢN PHẨM
                </Link>
            </div>

            <DataTable 
                :title="status === 'trash' ? 'Danh sách sản phẩm ngừng kinh doanh' : 'Danh sách sản phẩm đang kinh doanh'"
                :headers="tableHeaders" 
                :items="products?.data" 
                :pagination="products" 
                v-model:search="searchTerm" 
                v-model:per-page="perPage"
                searchPlaceholder="Tìm theo tên hoặc sku..."
            >
                <template #row="{ item }">
                    <td class="px-5 py-4">
                        <p class="text-sm text-gray-800 font-medium">#{{item.id}}</p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <img :src="getPrimaryImage(item)" class="w-12 h-12 rounded-lg object-cover border" />
                            <div>
                                <p class="font-medium text-gray-800 text-md">{{ item.product_name }}</p>
                                <div class="flex gap-1 mt-1">
                                    <span v-for="v in item.variants.slice(0, 2)" :key="v.id" class="text-[10px] bg-gray-100 px-1 rounded text-gray-500">
                                        {{ v.sku }}
                                    </span>
                                    <span v-if="item.variants.length > 2" class="text-[10px] text-gray-400">...</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <p class="text-sm text-gray-800 font-medium">{{ item.brand?.brand_name }}</p>
                        <p class="text-xs text-gray-400">{{ item.category?.category_name }}</p>
                    </td>

                    <td class="px-5 py-4 text-sm font-bold text-gray-700">
                        {{ getDisplayPrice(item) }}
                    </td>

                    <td class="px-5 py-4">
                        <span :class="getTotalStock(item) > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                            {{ getTotalStock(item) }} sản phẩm
                        </span>
                    </td>

                    <td class="px-5 py-4">
                        <Badge variant="solid" size="sm" :color="item.is_active ? 'success' : 'error'">{{ item.is_active ? 'Hiển thị' : 'Ẩn' }} </Badge>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-begin gap-3">
                            <template v-if="status === 'active'">
                                <Link v-if="can('products.view')" :href="route('admin.products.show', item.id)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </Link>
                                <Link v-if="can('products.edit')" :href="route('admin.products.edit', item.id)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="1.5"/></svg>
                                </Link>
                                <DeleteAction v-if="can('products.delete')" :message="`Bạn có chắc chắn muốn tạm xóa sản phẩm ${item.product_name}?`" :item="item" routeName="admin.products.destroy" :displayName="item.product_name" />
                            </template>
                            
                            <template v-else>
                                <ConfirmAction
                                    v-if="can('products.delete')"
                                    title="Khôi phục sản phẩm"
                                    :message="`Bạn có muốn khôi phục lại sản phẩm ${item.product_name}?`"
                                    variant="primary"
                                    confirm-text="Khôi phục"
                                    @confirm="handleRestore(item.id)"
                                >
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-green-600 transition-colors" title="Khôi phục">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </template>
                                </ConfirmAction>
                                
                                <ConfirmAction
                                    v-if="can('products.delete')"
                                    title="Xóa vĩnh viễn"
                                    message="Hành động này không thể hoàn tác. Mọi dữ liệu của sản phẩm này sẽ bị xóa vĩnh viễn!"
                                    variant="danger"
                                    confirm-text="Xóa vĩnh viễn"
                                    @confirm="handleForceDelete(item.id)"
                                >
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-red-600 transition-colors" title="Xóa vĩnh viễn">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </template>
                                </ConfirmAction>
                            </template>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>