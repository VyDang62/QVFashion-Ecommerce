<template>
  <Head title="Thống kê Kho & Lô hàng" />
  <AdminLayout title="Thống kê Kho & Lô hàng">
    <PageBreadcrumb pageTitle="Thống kê Kho & Lô hàng" />
    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <p class="text-sm font-medium text-gray-500">Tổng giá trị vốn trong kho</p>
          <h4 class="text-2xl font-black text-gray-900 mt-2">{{ formatPrice(metrics.total_inventory_value) }}</h4>
          <p class="text-xs text-blue-600 mt-2 font-bold">Tổng tiền hàng chưa bán</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <p class="text-sm font-medium text-gray-500">Vòng quay hàng tồn (30 ngày)</p>
          <h4 class="text-2xl font-black text-gray-900 mt-2">{{ metrics.turnover_rate }}x</h4>
          <p class="text-xs text-emerald-600 mt-2 font-bold">Tốc độ luân chuyển hàng</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-red-100 shadow-sm">
          <p class="text-sm font-medium text-gray-500">Sản phẩm hết hàng</p>
          <h4 class="text-2xl font-black text-red-600 mt-2">{{ metrics.out_of_stock_count }}</h4>
          <p class="text-xs text-red-400 mt-2">Cần nhập thêm</p>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-7 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Dòng vốn</h3>
          <VueApexCharts type="treemap" height="350" :options="treeMapOptions" :series="treeMapSeries" />
        </div>

        <div class="col-span-12 lg:col-span-5 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Cảnh báo tồn kho</h3>
            <div class="flex gap-2">
                <button 
                    v-if="can('dashboard.export_inventory')"
                    @click="exportToExcel" 
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all text-sm font-bold shadow-sm"
                >
                <ExcelIcon/>
                    Xuất Excel
                </button>

                <button
                    v-if="can('dashboard.export_inventory')"
                    @click="exportToPdf" 
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all text-sm font-bold shadow-md shadow-red-100"
                >
                <PdfIcon/>
                    Xuất PDF
                </button>
            </div>
          </div>
          
          
          <div class="space-y-4">
            <div v-for="item in lowStockItems" :key="item.sku" class="flex items-center justify-between p-3 bg-red-50/50 border border-red-100 rounded-xl">
              <div>
                <p class="text-sm font-bold text-gray-800">{{ item.product_name }}</p>
                <p class="text-xs text-gray-500">SKU: {{ item.sku }}</p>
              </div>
              <div class="text-right">
                <span class="text-lg font-black text-red-600">{{ item.stock_quantity }}</span>
                <span class="text-[10px] text-gray-400 block uppercase">Ngưỡng: {{ item.low_stock_threshold }}</span>
              </div>
            </div>
            <div v-if="lowStockItems.length === 0" class="text-center py-10 text-gray-400">Không có cảnh báo</div>
          </div>
        </div>
      </div>
      <DataTable
          title="Hiệu suất lợi nhuận theo Lô hàng"
          :headers="tableHeaders"
          :items="batchAnalysis.data"
          :pagination="batchAnalysis"
          v-model:search="searchTerm"
          v-model:per-page="perPage"
          searchPlaceholder="Tìm theo tên sản phẩm hoặc mã lô..."
      >
          <template #row="{ item }">
              <td class="px-5 py-4">
                  <div class="font-mono text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded w-fit">
                      {{ item.batch_code }}
                  </div>
                  <div class="text-[10px] text-gray-400 mt-1">{{ formatDate(item.created_at) }}</div>
              </td>
              
              <td class="px-5 py-4">
                  <div class="text-sm font-bold text-gray-800">{{ item.product_name }}</div>
                  <div class="text-xs text-gray-500">SKU: {{ item.sku }}</div>
              </td>

              <td class="px-5 py-4 text-sm text-gray-700 font-medium">
                  {{ formatPrice(item.purchase_price) }}
              </td>

              <td class="px-5 py-4 text-sm text-gray-900 font-bold">
                  {{ formatPrice(item.selling_price) }}
              </td>

              <td class="px-5 py-4">
                  <div :class="['text-sm font-black', item.margin > 30 ? 'text-emerald-600' : 'text-orange-600']">
                      {{ item.margin }}%
                  </div>
              </td>

              <td class="px-5 py-4">
                  <Badge :color="item.margin > 30 ? 'success' : 'warning'" size="sm" variant="light">
                      {{ item.margin > 30 ? 'Lợi nhuận cao' : 'Lợi nhuận TB' }}
                  </Badge>
              </td>
          </template>
      </DataTable>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { debounce } from 'lodash';
import VueApexCharts from 'vue3-apexcharts';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import { useFormatter } from '@/composables/useFormatter';
import { Head, router } from '@inertiajs/vue3';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import DataTable from '@/components/admin/tables/DataTable.vue';
import PdfIcon from '@/icons/PdfIcon.vue';
import ExcelIcon from '@/icons/ExcelIcon.vue';
import { usePermission } from '@/composables/usePermission';

const { can } = usePermission();
const { formatPrice, formatDate } = useFormatter();
const props = defineProps(['inventoryValue', 'lowStockItems', 'batchAnalysis', 'metrics', 'filters']);

const tableHeaders = ['Mã Lô', 'Sản phẩm', 'Giá nhập', 'Giá bán', 'Biên LN (%)', 'Trạng thái'];

const searchTerm = ref(props.filters.search || '');
const perPage = ref(props.filters.perPage || 10);

watch([searchTerm, perPage], debounce(([newSearch, newPerPage]) => {
    router.get(route('admin.dashboard.inventorystatistics'), 
        { search: newSearch, perPage: newPerPage }, 
        { 
            preserveState: true, 
            replace: true,
            preserveScroll: true,
            only: ['batchAnalysis', 'filters'] 
        }
    );
}, 500));

//Cấu hình Treemap cho Giá trị tồn kho
const treeMapSeries = computed(() => [{
  data: props.inventoryValue.map(item => ({
    x: item.category,
    y: parseFloat(item.total_value)
  }))
}]);

const treeMapOptions = {
  legend: { show: false },
  chart: { toolbar: { show: false }, animations: { enabled: false }, fontFamily: 'Outfit, sans-serif' },
  colors: ['#465FFF', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
  plotOptions: {
    treemap: { distributed: true, enableShades: true }
  },
  tooltip: {
    y: { formatter: (val) => formatPrice(val) }
  }
};

const exportToExcel = () => {
    //Chuyển hướng trình duyệt đến route export để kích hoạt tải file
    window.location.href = route('admin.dashboard.inventorystatistics.exportExcel');
};

const exportToPdf = () => {
    // Gọi route xuất PDF
    window.location.href = route('admin.dashboard.inventorystatistics.exportPdf');
};
</script>