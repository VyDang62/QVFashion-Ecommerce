<template>
  <Head title="Báo cáo Tài chính" />
  <AdminLayout title="Báo cáo Tài chính">
    <PageBreadcrumb pageTitle="Báo cáo Tài chính" />
    <div class="space-y-6">
      <div class="flex flex-col gap-4 p-5 bg-white border border-gray-200 rounded-2xl shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-800 tracking-tight">Báo cáo tài chính</h2>
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
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
          <p class="text-xs font-bold text-gray-600 uppercase">Lợi nhuận ròng (Net Profit)</p>
          <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ formatPrice(summary.net_profit) }}</h3>
          <p class="text-[10px] text-gray-600 mt-2 font-bold">* Đã trừ Giá vốn hàng bán & Thuế {{ taxRate * 100 }}%</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
          <p class="text-xs font-bold text-gray-600 uppercase">Dòng tiền thực thu</p>
          <h3 class="text-2xl font-black text-blue-600 mt-1">{{ formatPrice(summary.collected_cash) }}</h3>
          <p class="text-[10px] text-blue-400 mt-2 font-bold">Tiền đã về ví</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
          <p class="text-xs font-bold text-gray-600 uppercase">Dòng tiền đang treo</p>
          <h3 class="text-2xl font-black text-orange-500 mt-1">{{ formatPrice(summary.pending_cash) }}</h3>
          <p class="text-[10px] text-orange-400 mt-2 font-bold">Đang vận chuyển/xử lý</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
          <p class="text-xs font-bold text-gray-600 uppercase">Thuế dự tính</p>
          <h3 class="text-2xl font-black text-red-500 mt-1">{{ formatPrice(summary.estimated_tax) }}</h3>
          <p class="text-[10px] text-red-400 mt-2 font-bold">Dựa trên doanh thu</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Chi tiết</h3>

            <div class="flex items-center gap-3">
                <button
                    v-if="can('dashboard.export_financial')"
                    @click="exportToExcel" 
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all text-sm font-bold shadow-sm"
                >
                <ExcelIcon/>
                    Xuất File Kế Toán (.xlsx)
                </button>

                <button
                    v-if="can('dashboard.export_financial')"
                    @click="exportToPdf" 
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all text-sm font-bold shadow-md shadow-red-100"
                >
                <PdfIcon/>
                    Xuất PDF (.pdf)
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold">
              <tr>
                <th class="px-6 py-4 text-xs font-medium">Mã đơn / Ngày</th>
                <th class="px-6 py-4 text-xs text-right font-medium">Doanh thu thuần</th>
                <th class="px-6 py-4 text-xs text-right font-medium ">Giá vốn (COGS)</th>
                <th class="px-6 py-4 text-xs text-right font-medium">Voucher</th>
                <th class="px-6 py-4 text-xs text-right font-medium">Lợi nhuận gộp</th>
                <th class="px-6 py-4 text-xs text-center font-medium">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="order in financialData" :key="order.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="px-6 py-4">
                  <p class="font-bold text-gray-800">#{{ order.order_code.substring(0, 8) }}</p>
                  <p class="text-[10px] text-gray-400">{{ formatDate(order.created_at) }}</p>
                </td>
                <td class="px-6 py-4 text-right font-medium">{{ formatPrice(order.final_amount) }}</td>
                <td class="px-6 py-4 text-right text-gray-500">{{ formatPrice(order.total_cogs) }}</td>
                <td class="px-6 py-4 text-right text-red-400">-{{ formatPrice(order.discount_amount) }}</td>
                <td class="px-6 py-4 text-right font-bold" :class="order.final_amount - order.total_cogs > 0 ? 'text-emerald-600' : 'text-red-500'">
                  {{ formatPrice(order.final_amount - order.total_cogs) }}
                </td>
                <td class="px-6 py-4 text-center">
                  <Badge :color="order.status_info.badge_admin">{{ order.status_info.label }}</Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { useFormatter } from '@/composables/useFormatter';
import ExcelIcon from '@/icons/ExcelIcon.vue';
import PdfIcon from '@/icons/PdfIcon.vue';
import Badge from '@/components/admin/ui/Badge.vue';
import PageBreadcrumb from '@/components/admin/common/PageBreadcrumb.vue';
import { usePermission } from '@/composables/usePermission';

const props = defineProps({
    financialData: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_cogs: 0,
            total_discount: 0,
            collected_cash: 0,
            pending_cash: 0,
            estimated_tax: 0,
            net_profit: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: ''
        })
    }
});
//Sử dụng Composable để định dạng dữ liệu
const {can} = usePermission();
const { formatPrice, formatDate } = useFormatter();
const taxRate = 0.1; // Thuế VAT 10% để hiển thị trên UI

//Khởi tạo Form lọc dữ liệu
const filterForm = reactive({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date
});

//Logic Lọc dữ liệu
const applyFilters = () => {
    router.get(route('admin.dashboard.financialreport'), filterForm, {
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

const exportToExcel = () => {
    //Tạo query string từ filterForm (ví dụ: ?start_date=2024-01-01&end_date=2024-01-31)
    const params = new URLSearchParams(filterForm).toString();
    
    //Chuyển hướng trình duyệt đến route export để kích hoạt tải file
    window.location.href = `${route('admin.dashboard.financialreport.export')}?${params}`;
};

const exportToPdf = () => {
    const params = new URLSearchParams(filterForm).toString();
    // Gọi route xuất PDF
    window.location.href = `${route('admin.dashboard.financialreport.exportpdf')}?${params}`;
};
</script>