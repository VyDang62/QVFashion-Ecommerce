<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BatchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['index']),

            new Middleware('can:inventory.edit', only: ['edit', 'adjust']),
        ];
    }
    public function index(Request $request)
    {
        $query = Batch::with(['variant.product','receipt.supplier']);

        if($request->filled('search')){
            $query->where('batch_code','like','%'.$request->search.'%');
        }

        if ($request->status === 'out_of_stock') {
            $query->where('remaining_quantity', 0);
        } elseif ($request->status === 'low_stock') {
            $query->whereRaw('remaining_quantity > 0 AND remaining_quantity < (original_quantity * 0.2)');
        }

        $batches = $query->orderBy('received_date', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('admin/Inventory/Index', [
            'batches' => $batches,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function edit($id)
    {
        $batch = Batch::with(['variant.product'])->findOrFail($id);
        
        return Inertia::render('admin/Inventory/Edit', [
            'batch' => $batch
        ]);
    }
    public function adjust(Request $request, $id)
    {
        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $batch = Batch::findOrFail($id);
                
                $oldQuantity = $batch->remaining_quantity;
                $newQuantity = $request->new_quantity;
                
                if ($newQuantity > $batch->original_quantity) {
                    return back()->with('error', "Số lượng điều chỉnh ({$newQuantity}) không được vượt quá số lượng gốc ban đầu ({$batch->original_quantity})!");
                }

                $batch->update([
                    'remaining_quantity' => $newQuantity
                ]);

                Logger::log(
                    'Update Inventory',
                    $batch,                
                    "Điều chỉnh số lượng lô hàng {$batch->batch_code}: {$oldQuantity} -> {$newQuantity}",
                    [
                        'old_qty' => $oldQuantity,
                        'new_qty' => $newQuantity,
                        'reason'  => $request->reason,
                        'product_name' => $batch->variant->product->product_name ?? 'N/A',
                        'sku' => $batch->variant->sku ?? 'N/A'
                    ]
                );
            });
            return back()->with('success', 'Điều chỉnh tồn kho thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi điều chỉnh: ' . $e->getMessage());
        }
    }
}
