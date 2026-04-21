<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\Logger;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class GoodsReceiptController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:goods-receipts.view', only: ['index', 'show']),

            new Middleware('can:goods-receipts.create', only: ['create', 'store']),

            new Middleware('can:goods-receipts.edit', only: ['edit', 'update']),

            new Middleware('can:goods-receipts.approve', only: ['approve']),

            new Middleware('can:goods-receipts.cancel', only: ['cancel', 'destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage',10);
        $query = GoodsReceipt::with(['supplier','user']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('receipt_code', 'ilike', '%' . $searchTerm . '%')
                ->orWhereHas('supplier', function ($sub) use ($searchTerm) {
                    $sub->where('supplier_name', 'ilike', '%' . $searchTerm . '%');
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('receipt_status', $request->status);
        }

        $receipts = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/GoodsReceipts/Index', [
            'receipts' => $receipts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::all();
    
        $variants = ProductVariant::with(['product', 'attributeValues.attribute', 'images'])->get();

        return Inertia::render('admin/GoodsReceipts/Create', [
            'suppliers' => $suppliers,
            'variants' => $variants
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'details' => 'required|array|min:1',
            'details.*.product_variant_id' => 'required|exists:product_variants,id',
            'details.*.received_quantity' => 'required|integer|min:1',
            'details.*.purchase_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                do {
                    $receiptCode = 'PN-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
                } while (GoodsReceipt::where('receipt_code', $receiptCode)->exists());
                //Tạo phiếu nhập
                $receipt = GoodsReceipt::create([
                    'receipt_code' => $receiptCode,
                    'supplier_id' => $request->supplier_id,
                    'user_id' => Auth::id() ?? 2,
                    'total_cost'     => $request->total_cost,
                    'receipt_status' => $request->receipt_status,
                    'receipt_date'   => now(),
                ]);

                foreach ($request->details as $item) {
                    //Lưu Chi tiết
                    $receipt->details()->create([
                        'product_variant_id' => $item['product_variant_id'],
                        'received_quantity'  => $item['received_quantity'],
                        'purchase_price'     => $item['purchase_price'],
                        'sub_total'          => $item['sub_total'],
                    ]);

                    //Nếu trạng thái là 'completed', tạo batch
                    if ($request->receipt_status === 'completed') {
                        Batch::create([
                            'goods_receipt_id'   => $receipt->id,
                            'product_variant_id' => $item['product_variant_id'],
                            'batch_code'         => 'BATCH-' . $receiptCode . '-' . $item['product_variant_id'],
                            'purchase_price'     => $item['purchase_price'],
                            'original_quantity'  => $item['received_quantity'],
                            'remaining_quantity' => $item['received_quantity'],
                            'received_date'      => now(),
                        ]);
                    }
                }

                Logger::log(
                    'Create Goods Receipt',
                    $receipt,
                    "Đã tạo phiếu nhập hàng: {$receipt->receipt_code} (" . ($request->receipt_status == 'completed' ? 'Nhập kho ngay' : 'Đang chờ xử lý') . ")",
                    [
                        'total_cost' => $request->total_cost,
                        'supplier_id' => $request->supplier_id,
                        'status' => $request->receipt_status,
                        'items_count' => count($request->details)
                    ]
                );
            });
            return redirect()->route('admin.goodsreceipts.index')->with('success', 'Đã lưu phiếu nhập hàng thành công!');
        } catch (\Exception $e) {
            return back()->with(['error', 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GoodsReceipt $goodsreceipt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GoodsReceipt $goodsreceipt)
    {
        $goodsreceipt->load('details.variant.product');

        return Inertia::render('admin/GoodsReceipts/Edit',[
            'goodsreceipt' => $goodsreceipt,
            'suppliers' => Supplier::all(['id','supplier_name']),
            'variants' => ProductVariant::with(['product', 'attributeValues.attribute', 'images'])->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GoodsReceipt $goodsreceipt)
    {
        
        if ($goodsreceipt->receipt_status !== 'pending') {
            return back()->with(['error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Chờ xử lý.']);
        }

        $validate = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'total_cost' => 'required|numeric',
            'details' => 'required|array|min:1',
            'details.*.product_variant_id' => 'required|exists:product_variants,id',
            'details.*.received_quantity' => 'required|integer|min:1',
            'details.*.purchase_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validate, $goodsreceipt) {
                $oldTotal = $goodsreceipt->total_cost;
                $goodsreceipt->update([
                    'supplier_id' => $validate['supplier_id'],
                    'total_cost' => $validate['total_cost'],
                ]);

                $goodsreceipt->details()->delete();
                foreach ($validate['details'] as $item) {
                    $goodsreceipt->details()->create([
                        'product_variant_id' => $item['product_variant_id'],
                        'received_quantity' => $item['received_quantity'],
                        'purchase_price' => $item['purchase_price'],
                        'sub_total' => $item['received_quantity'] * $item['purchase_price'],
                    ]);
                }
                Logger::log(
                    'Update Goods Receipt',
                    $goodsreceipt,
                    "Đã cập nhật nội dung phiếu nhập: {$goodsreceipt->receipt_code}",
                    [
                        'old_total' => $oldTotal,
                        'new_total' => $validate['total_cost'],
                        'items_count' => count($validate['details'])
                    ]
                );
            });
            return redirect()->route('admin.goodsreceipts.index')->with('success', 'Cập nhật phiếu nhập thành công!');
        } catch (\Exception $e) {
            return back()->with(['error', 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GoodsReceipt $goodsreceipt)
    {
        if ($goodsreceipt->receipt_status !== 'pending') {
            return back()->with('error', 'Không thể xóa phiếu đã hoàn thành hoặc đã hủy!');
        }

        try {
            $code = $goodsreceipt->receipt_code;
            $receiptId = $goodsreceipt->id;
            DB::transaction(function () use ($goodsreceipt, $code, $receiptId) {
                $goodsreceipt->details()->delete();
                $goodsreceipt->delete();
                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Delete Goods Receipt',
                    'model_type' => GoodsReceipt::class,
                    'model_id' => $receiptId,
                    'description' => "Đã xóa vĩnh viễn phiếu nhập đang chờ xử lý: {$code}",
                    'properties' => json_encode(['receipt_code' => $code]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return redirect()->route('admin.goodsreceipts.index')->with('success', "Đã xóa thành công phiếu nhập đang chờ xử lý {$code}.");

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi xóa: ' . $e->getMessage());
        }
    }

    public function approve(GoodsReceipt $goodsreceipt)
    {
        if ($goodsreceipt->receipt_status !== 'pending') {
            return back()->with('error', 'Chỉ có thể duyệt phiếu ở trạng thái chờ xử lý.');
        }

        if($goodsreceipt->details()->Empty()){
            return back()->with('error','Không thể duyệt phiếu nhập không có sản phẩm!');
        }
        try{
            DB::transaction(function () use ($goodsreceipt) {
                $goodsreceipt->update(['receipt_status' => 'completed']);

                foreach ($goodsreceipt->details as $item) {
                    Batch::create([
                        'product_variant_id' => $item->product_variant_id,
                        'goods_receipt_id'   => $goodsreceipt->id,
                        'batch_code'         => 'BATCH-' . $goodsreceipt->receipt_code . '-' . $item->product_vanriant_id,
                        'purchase_price'     => $item->purchase_price,
                        'original_quantity'  => $item->received_quantity,
                        'remaining_quantity' => $item->received_quantity,
                        'received_date'      => now(),
                    ]);
                }
                Logger::log(
                    'approve',
                    $goodsreceipt,
                    "Đã duyệt phiếu nhập: {$goodsreceipt->receipt_code}",
                );
            });
            return back()->with('success', 'Duyệt phiếu nhập và nhập kho thành công!');
        }catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi duyệt phiếu: ' . $e->getMessage());
        }
        
    }
    public function cancel(GoodsReceipt $goodsreceipt)
    {
        if($goodsreceipt->receipt_status === 'cancelled'){
            return back()->with('error','Phiếu nhập đã được hủy!');
        }
        try{
            DB::transaction(function () use ($goodsreceipt) {
                if ($goodsreceipt->receipt_status === 'completed') {
                    $batches = Batch::where('goods_receipt_id', $goodsreceipt->id)->get();
                    foreach ($batches as $batch) {
                        if ($batch->remaining_quantity < $batch->original_quantity) {
                            throw new \Exception("Lô hàng {$batch->batch_code} đã có sản phẩm được bán/xuất, không thể hủy!");
                        }
                        if (!$batch instanceof \Illuminate\Database\Eloquent\Model) {
                            throw new \Exception("Lỗi kiểu dữ liệu: " . get_class($batch));
                        }
                        $batch->delete(); 
                    }
                }
                $goodsreceipt->update(['receipt_status' => 'cancelled']);

                Logger::log(
                    'cancel', 
                    $goodsreceipt, 
                    "Đã hủy phiếu nhập: {$goodsreceipt->receipt_code}"
                );
            });

            return back()->with('success', 'Đã hủy phiếu nhập thành công!');
        }catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi hủy phiếu: ' . $e->getMessage());
        }
        
    }
}
