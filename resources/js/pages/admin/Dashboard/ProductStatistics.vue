<template>
  <Head title="Thống kê Sản phẩm" />
  <AdminLayout title="Thống kê Sản phẩm">
    <PageBreadcrumb pageTitle="Thống kê Sản phẩm" />
    <div class="space-y-6">
      <div class="flex flex-col gap-4 p-5 bg-white border border-gray-200 rounded-2xl shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-800 tracking-tight">Thống kê sản phẩm</h2>
          <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
            <i class="ri-calendar-line"></i> Dữ liệu: {{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-xl border border-gray-100">
            <input type="date" v-model="filterForm.start_date" class="bg-transparent border-none text-sm focus:ring-0 px-2 py-1" />
            <span class="text-gray-300">|</span>
            <input type="date" v-model="filterForm.end_date" class="bg-transparent border-none text-sm focus:ring-0 px-2 py-1" />
          </div>
          <button @click="applyFilters" class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-blue-100 flex items-center gap-2">
            Lọc dữ liệu
          </button>
          <button 
              @click="resetFilters"
              class="px-4 py-2 bg-white text-gray-600 border border-gray-200 text-sm font-bold rounded-lg hover:bg-gray-50 transition-all"
          >
              Làm mới
          </button>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-6 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
            <h3 class="text-lg font-bold text-gray-800">Top 10 Sản phẩm bán chạy (Số lượng)</h3>
          </div>
          <div v-if="topSelling?.length > 0">
            <VueApexCharts type="bar" height="380" :options="sellingOptions" :series="sellingSeries" />
          </div>
          <div v-else class="h-[380px] flex flex-col items-center justify-center text-gray-400 gap-3">
             <i class="ri-inbox-line text-4xl"></i>
             <p class="italic">Không có dữ liệu bán hàng</p>
          </div>
        </div>

        <div class="col-span-12 lg:col-span-6 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-6 bg-emerald-500 rounded-full"></div>
            <h3 class="text-lg font-bold text-gray-800">Top 10 Sản phẩm doanh thu cao</h3>
          </div>
          <div v-if="topRevenue?.length > 0">
            <VueApexCharts type="bar" height="380" :options="revenueOptions" :series="revenueSeries" />
          </div>
          <div v-else class="h-[380px] flex flex-col items-center justify-center text-gray-400 gap-3">
             <i class="ri-money-dollar-circle-line text-4xl"></i>
             <p class="italic">Chưa có dữ liệu doanh thu</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 xl:col-span-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Tỷ trọng doanh thu danh mục</h3>
          <div v-if="categoryStats.length > 0">
            <VueApexCharts type="donut" height="380" :options="categoryOptions" :series="categorySeries"/>
          </div>
          <div v-else class="h-[380px] flex items-center justify-center text-gray-400 italic">
            Chưa có dữ liệu
          </div>
        </div>

        <div class="col-span-12 xl:col-span-8 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Phân tích lợi nhuận chi tiết</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead>
                <tr class="text-gray-400 text-xs uppercase tracking-wider border-b">
                    <th class="pb-4 pl-4 font-medium">Danh mục</th>
                    <th class="pb-4 text-right font-medium">Doanh thu thực</th>
                    <th class="pb-4 text-right font-medium">Lợi nhuận</th>
                    <th class="pb-4 text-right font-medium">Tỷ suất</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="parent in categoryStats" :key="parent.name">
                    <tr class="bg-gray-50/40 border-b hover:bg-gray-100 transition-colors">
                        <td class="py-4 pl-4 font-bold text-gray-800 flex items-center gap-2">
                          <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                          {{ parent.name }}
                        </td>
                        <td class="py-4 font-bold text-right text-gray-700">{{ formatPrice(parent.revenue) }}</td>
                        <td class="py-4 text-right text-emerald-600 font-bold">{{ formatPrice(parent.profit) }}</td>
                        <td class="py-4 text-right">
                          <Badge color="primary" size="sm" variant="solid">{{ calculateMargin(parent.revenue, parent.profit) }}%</Badge>
                        </td>
                    </tr>
                    <tr v-for="child in parent.children" :key="child.name" class="border-b border-dashed border-gray-100 hover:bg-blue-50/20">
                        <td class="py-3.5 pl-10 text-sm text-gray-500 italic">└─ {{ child.name }}</td>
                        <td class="py-3.5 text-right text-sm text-gray-600">{{ formatPrice(child.revenue) }}</td>
                        <td class="py-3.5 text-right text-sm text-emerald-600 font-medium">{{ formatPrice(child.profit) }}</td>
                        <td class="py-3.5 text-right">
                          <span class="text-xs font-bold px-2 py-1 rounded-lg" :class="getMarginClass(calculateMargin(child.revenue, child.profit))">
                              {{ calculateMargin(child.revenue, child.profit) }}%
                          </span>
                        </td>
                    </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-2xl border border-red-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-red-400 rounded-lg">
              <i class="ri-error-warning-line text-xl"></i>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-800">Sản phẩm hiệu suất thấp</h3>
              <p class="text-xs text-gray-600">Top 5 sản phẩm có lượt bán thấp nhất trong kỳ</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <div v-for="item in lowPerformance" :key="item.id" class="p-4 rounded-2xl bg-gray-50 border border-gray-100 hover:border-red-200 hover:bg-red-50/20 transition-all group">
             <div class="flex flex-col h-full justify-between">
                <span class="text-sm font-bold text-gray-800 mb-2 truncate group-hover:text-red-600" :title="item.name">{{ item.name }}</span>
                <div class="flex items-end justify-between">
                  <div>
                    <p class="text-xs text-gray-600 font-semibold">Đã bán</p>
                    <p class="text-lg font-black text-gray-900">{{ item.sold_qty }}</p>
                  </div>
                  <div class="text-red-500 opacity-20 group-hover:opacity-100 transition-opacity">
                    <i class="ri-arrow-right-down-line text-2xl"></i>
                  </div>
                </div>
             </div>
          </div>
          <div v-if="lowPerformance.length === 0" class="col-span-full py-10 text-center text-gray-400 italic border-2 border-dashed border-gray-100 rounded-2xl">
            Tuyệt vời! Không có sản phẩm nào có hiệu suất quá thấp trong kỳ này.
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import Badge from '@/components/admin/ui/Badge.vue';
import { useFormatter } from '@/composables/useFormatter';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';

const { formatPrice,formatDate } = useFormatter();

const props = defineProps({ 
    topSelling: { type: Array, default: () => [] }, 
    categoryStats: { 
        type: Array, 
        default: () => []
    }, 
    filters: {
        type: Object,
        default: () => ({ start_date: '', end_date: '' })
    },
    topRevenue: { type: Array, default: () => [] }, 
    lowPerformance: { type: Array, default: () => [] }, 
});

// KHỞI TẠO FORM LỌC TỪ PROPS CỦA SERVER
const filterForm = reactive({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date
});

// HÀM GỬI YÊU CẦU LỌC
const applyFilters = () => {
    router.get(route('admin.dashboard.productstatistics'), filterForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const resetFilters = () => {
    filterForm.start_date = '';
    filterForm.end_date = '';
    applyFilters();
};
// Cấu hình Biểu đồ cột ngang (Dùng computed để tự động cập nhật khi dữ liệu thay đổi)
const sellingSeries = computed(() => [{
    name: 'Số lượng bán',
    data: props.topSelling.map(item => item.total_qty)
}]);

const sellingOptions = computed(() => ({
    chart: { type: 'bar', fontFamily: 'Outfit, sans-serif', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '50%' } },
    colors: ['#465FFF'],
    xaxis: { categories: props.topSelling.map(item => item.name) },
    yaxis: {
        labels: {
            show: true,
            style: { fontSize: '13px', fontWeight: 500 },
            maxWidth: 180, 
        }
    },
    tooltip: { shared: true, intersect: false }
}));

// Cấu hình Biểu đồ Donut
const categoryOptions = computed(() => ({
    labels: props.categoryStats.map(c => c.name),
    colors: ['#465FFF', '#9CB9FF', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
    legend: { position: 'bottom', fontFamily: 'Outfit, sans-serif', fontSize: '14px',},
    plotOptions: { 
        pie: { 
            donut: { 
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Tổng doanh thu',
                        fontSize: '16px',
                        fontWeight: 550,
                        formatter: () => {
                            const total = props.categoryStats.reduce((acc, cur) => acc + cur.revenue, 0);
                            return formatPrice(total);
                        }
                    },
                    value: {
                        show: true,
                        fontSize: '18px',
                        fontWeight: 600,
                        offsetY: 5, 
                        formatter: (val) => formatPrice(val)
                    }
                }
            } 
        } 
    },
    dataLabels: { 
        enabled: true,
        formatter: (val) => val.toFixed(1) + "%",
        style: { fontSize: '12px', fontWeight: 500 }
    }
}));

const categorySeries = computed(() => props.categoryStats.map(c => c.revenue));

const calculateMargin = (revenue, profit) => {
    if (!revenue || revenue === 0) return 0;
    return ((profit / revenue) * 100).toFixed(1);
};
const getMarginClass = (margin) => {
    const val = parseFloat(margin);
    if (val <= 0) return 'text-red-600 bg-red-50';      
    if (val < 15) return 'text-orange-600 bg-orange-50'; 
    return 'text-green-600 bg-green-50';               
};

const revenueSeries = computed(() => [{
    name: 'Doanh thu thực tế',
    data: props.topRevenue.map(item => item.revenue)
}]);

const revenueOptions = computed(() => ({
    chart: { type: 'bar', fontFamily: 'Outfit, sans-serif', toolbar: { show: false } },
    plotOptions: { 
        bar: { 
            horizontal: true, 
            borderRadius: 4, 
            barHeight: '60%',
            distributed: true 
        } 
    },
    colors: ['#10B981', '#34D399', '#6EE7B7', '#A7F3D0', '#D1FAE5'], 
    xaxis: { 
        categories: props.topRevenue.map(item => item.name),
        labels: {
            formatter: (val) => formatPrice(val),
            style: { fontSize: '11px' }
        }
    },
    yaxis: {
        labels: {
            show: true,
            style: { fontSize: '13px', fontWeight: 500 },
            maxWidth: 180, 
        }
    },
    dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: { colors: ['#fff'], fontSize: '11px' },
        formatter: (val) => formatPrice(val), 
        offsetX: 0,
    },
    tooltip: {
        y: { formatter: (val) => formatPrice(val) }
    },
    legend: { show: false }
}));
</script>