<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LowStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->whereRaw('product_variants.stock_quantity <= product_variants.low_stock_threshold')
            ->whereNull('product_variants.deleted_at')
            ->select('products.product_name', 'product_variants.sku', 'product_variants.stock_quantity', 'product_variants.low_stock_threshold')
            ->get();
    }

    public function headings(): array
    {
        return ['Tên Sản Phẩm', 'Mã SKU', 'Số Lượng Tồn', 'Ngưỡng Cảnh Báo', 'Trạng Thái'];
    }

    public function map($item): array
    {
        return [
            $item->product_name,
            $item->sku,
            $item->stock_quantity,
            $item->low_stock_threshold,
            $item->stock_quantity == 0 ? 'Hết hàng' : 'Sắp hết hàng'
        ];
    }
}