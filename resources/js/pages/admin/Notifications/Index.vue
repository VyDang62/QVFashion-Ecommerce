<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import { useFormatter } from '@/composables/useFormatter';

const props = defineProps({
    notifications: Object,
    filters: Object
});

const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 15);
const filterStatus = ref(props.filters.filter || 'all');

const { formatDateTime } = useFormatter();

const tableHeaders = ['Loại', 'Nội dung', 'Thời gian', 'Trạng thái', 'Thao tác'];

watch([searchTerm, perPage, filterStatus], debounce(([newSearch, newPerPage, newFilter]) => {
    router.get(route('admin.notifications.index'), { 
        search: newSearch, 
        perPage: newPerPage, 
        filter: newFilter 
    }, { preserveState: true, replace: true });
}, 500));

const handleNotificationClick = (notification) => {
    const type = notification.data?.type;

    const redirectToTarget = () => {
        switch (type) {
            case 'new_order':
                if (notification.data?.order_id) {
                    router.get(route('admin.orders.edit', notification.data.order_id));
                } else {
                    router.get(route('admin.orders.index'));
                }
                break;

            case 'low_stock':
                router.get(route('admin.products.index'), {
                    search: notification.data?.product_name || ''
                });
                break;
            default:
                break;
        }
    };

    if (!notification.read_at) {
        router.patch(route('admin.notifications.markAsRead', notification.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                notification.read_at = new Date().toISOString();
                redirectToTarget();
            }
        });
    } else {
        redirectToTarget();
    }
};

const markAllAsRead = () => {
    if (props.notifications.data.some(n => !n.read_at) || props.filters.unread_count > 0) {
        router.patch(route('admin.notifications.markAllAsRead'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                props.notifications.data.forEach(n => {
                    if (!n.read_at) n.read_at = new Date().toISOString();
                });
            }
        });
    }
};

const getNotificationStyle = (type) => {
    switch (type) {
        case 'low_stock':
            return {
                wrapper: 'bg-orange-100 text-orange-600',
                icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`
            }
        case 'new_order':
            return {
                wrapper: 'bg-green-100 text-green-600',
                icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>`
            }
        default:
            return {
                wrapper: 'bg-blue-100 text-blue-600',
                icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
            }
    }
}
</script>

<template>
    <Head title="Tất cả thông báo" />
    <AdminLayout>
        <PageBreadcrumb pageTitle="Thông báo hệ thống" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex gap-6">
                    <button 
                        @click="filterStatus = 'all'"
                        :class="['pb-4 px-1 text-sm font-bold transition-all border-b-2', filterStatus === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
                    >
                        Tất cả
                    </button>
                    <button 
                        @click="filterStatus = 'unread'"
                        :class="['pb-4 px-1 text-sm font-bold transition-all border-b-2 flex items-center gap-2', filterStatus === 'unread' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
                    >
                        Chưa đọc
                        <span v-if="$page.props.auth.unread_count > 0" class="bg-orange-500 text-white text-[10px] px-2 py-0.5 rounded-full shadow-sm">
                            {{ $page.props.auth.unread_count }}
                        </span>
                    </button>
                </div>

                <button v-if="$page.props.auth.unread_count > 0" @click="markAllAsRead"
                      class="px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    ĐÁNH DẤU TẤT CẢ ĐÃ ĐỌC
                </button>
            </div>

            <DataTable 
                :headers="tableHeaders" 
                :items="notifications.data" 
                :pagination="notifications"
                v-model:search="searchTerm"
                v-model:per-page="perPage"
                searchPlaceholder="Tìm kiếm nội dung thông báo..."
            >
                <template #row="{ item }">
                        <td class="px-5 py-4 w-16">
                            <div 
                                class="flex items-center justify-center w-10 h-10 rounded-full mx-auto"
                                :class="getNotificationStyle(item.data?.type).wrapper"
                                v-html="getNotificationStyle(item.data?.type).icon"
                            >
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div :class="['text-md', !item.read_at ? 'font-medium text-gray-900' : 'text-gray-600']">
                                <span class="text-blue-600 mr-1" v-if="item.data?.label">[{{ item.data.label }}]</span>
                                {{ item.data?.message }}
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-500">
                            {{ formatDateTime(item.created_at) }}
                        </td>
                        <td class="px-5 py-4">
                            <Badge :color="item.read_at ? 'light' : 'warning'" size="sm">
                                {{ item.read_at ? 'Đã xem' : 'Mới' }}
                            </Badge>
                        </td>
                        <td class="px-5 py-4 text-right items-begin">
                            <div class="flex items-center justify-begin gap-3">
                                <button @click="handleNotificationClick(item)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>