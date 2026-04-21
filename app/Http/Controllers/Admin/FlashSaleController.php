<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FlashSaleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:flashsales.view', only: ['index', 'show']),

            new Middleware('can:flashsales.create', only: ['create', 'store']),

            new Middleware('can:flashsales.edit', only: ['edit', 'update']),

            new Middleware('can:flashsales.delete', only: ['destroy', 'restore', 'forceDelete']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status', 'active');
        $search = $request->input('search');

        $query = ($status === 'trash') ? FlashSale::onlyTrashed() : FlashSale::query();

        if($request->filled('search')){
            $query->where('name','ilike','%'.strtoupper($search).'%');
        }

        $flashSales = $query->withCount('items')->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/FlashSales/Index',[
            'flashSales' => $flashSales,
            'filter' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'status' => $status,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/FlashSales/Create',[
            'products' => Product::with('variants')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.sale_price' => 'required|numeric|min:0',
            'items.*.sale_quantity' => 'required|integer|min:1',
            'items.*.user_limit' => 'required|integer|min:1',
        ],[
            'start_date.after' => 'Thời gian bắt đầu phải sau thời điểm hiện tại!',
            'end_date.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu!',
            'items.required' => 'Vui lòng chọn ít nhất một sản phẩm tham gia!',
        ]);

        try {
            DB::transaction(function () use ($validate) {
                
                $flashSale = FlashSale::create([
                    'name' => $validate['name'],
                    'start_date' => $validate['start_date'],
                    'end_date' => $validate['end_date'],
                    'is_active' => $validate['is_active'],
                ]);
                
                foreach ($validate['items'] as $item) {
                    $variant = ProductVariant::find($item['product_variant_id']);
                    if ($item['sale_price'] >= $variant->price) {
                        throw new \Exception("Giá sale của SKU {$variant->sku} phải nhỏ hơn giá gốc!");
                    }
                    $flashSale->items()->create([
                        'product_variant_id' => $item['product_variant_id'],
                        'sale_price' => $item['sale_price'],
                        'sale_quantity' => $item['sale_quantity'],
                        'sold_quantity' => 0,
                        'user_limit' => $item['user_limit'],
                    ]);
                }

                Logger::log(
                    'Create Flash Sale',
                    $flashSale,
                    "Đã tạo Flash Sale mới: {$flashSale->name}",
                    [
                        'start_date' => $flashSale->start_date,
                        'end_date' => $flashSale->end_date,
                        'items_count' => count($validate['items']),
                        'is_active' => $flashSale->is_active
                    ]
                );
            });

            return redirect()->route('admin.flashsales.index')
                ->with('success', 'Đã tạo Flash Sale thành công!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(FlashSale $flashsale)
    {
        $flashsale->load(['items.variant.product', 'items.variant.images']);

        $total_revenue = collect($flashsale->items)->sum(fn($item) => $item->sale_price * $item->sold_quantity);

        $stats = [
            'total_items' => $flashsale->items->count(),
            'total_sold' => $flashsale->items->sum('sold_quantity'),
            'total_quantity' => $flashsale->items->sum('sale_quantity'),
            'total_revenue' => $total_revenue,
        ];
        return inertia('admin/FlashSales/Show', [
            'flashSale' => $flashsale,
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FlashSale $flashsale)
    {
        $flashsale->load('items.variant.product');

        return Inertia::render('admin/FlashSales/Edit',[
            'flashSale' => $flashsale,
            'products' => Product::with('variants')->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FlashSale $flashsale)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:flash_sale_items,id', 
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.sale_price' => 'required|numeric|min:0',
            'items.*.sale_quantity' => 'required|integer|min:1',
            'items.*.user_limit' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($validated, $flashsale) {
                $oldData = $flashsale->only(['name', 'start_date', 'end_date', 'is_active']);
                $flashsale->update([
                    'name' => $validated['name'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'is_active' => $validated['is_active'],
                ]);

                $formItemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
                $existingItems = $flashsale->items()->with('variant')->get();

                foreach ($existingItems as $dbItem) {
                    if (!in_array($dbItem->id, $formItemIds)) {
                        if ($dbItem->sold_quantity > 0) {
                            throw new \Exception("Không thể xóa biến thể {$dbItem->variant->sku} khỏi Flash Sale vì đã có {$dbItem->sold_quantity} sản phẩm được bán ra.");
                        }
                        $dbItem->delete();
                    }
                }

                foreach ($validated['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = $flashsale->items()->find($itemData['id']);
                        
                        $updateData = [
                            'sale_price' => $itemData['sale_price'],
                            'sale_quantity' => $itemData['sale_quantity'],
                            'user_limit' => $itemData['user_limit'],
                        ];
                        if ($item->sold_quantity == 0) {
                            $updateData['product_variant_id'] = $itemData['product_variant_id'];
                        }

                        $item->update($updateData);
                    } else {
                        $flashsale->items()->create([
                            'product_variant_id' => $itemData['product_variant_id'],
                            'sale_price' => $itemData['sale_price'],
                            'sale_quantity' => $itemData['sale_quantity'],
                            'sold_quantity' => 0,
                            'user_limit' => $itemData['user_limit'],
                        ]);
                    }
                }

                Logger::log(
                    'Update Flash Sale',
                    $flashsale,
                    "Đã cập nhật chiến dịch Flash Sale: {$flashsale->name}",
                    [
                        'old' => $oldData,
                        'new' => $flashsale->only(['name', 'start_date', 'end_date', 'is_active']),
                        'updated_items_count' => count($validated['items'])
                    ]
                );
            });

            return redirect()->route('admin.flashsales.index')
                ->with('success', 'Cập nhật Flash Sale thành công!');

        } catch (\Exception $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FlashSale $flashsale)
    {
        $hasSales = $flashsale->items()->where('sold_quantity','>',0)->exists();
        $hasOrders = $flashsale->orderDetails()->exists();
        if($hasSales || $hasOrders){
            return back()->with('error','Không thể xóa do có sản phẩm đã được bán!');
        }

        try {
            $flashSaleName = $flashsale->name;
            DB::transaction(function () use ($flashsale) {
                $flashsale->items()->delete();
                $flashsale->delete();
            });
            Logger::log(
                'Soft Delete Flash Sale',
                $flashsale,
                "Đã tạm xóa chiến dịch Flash Sale: {$flashSaleName}"
            );
            return back()->with('success', 'Đã chuyển Flash Sale và danh sách sản phẩm vào thùng rác!');

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi hệ thống xảy ra: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $flashSale = FlashSale::withTrashed()->findOrFail($id);
            DB::transaction(function () use ($flashSale) {
                $flashSale->restore();
                $flashSale->items()->withTrashed()->restore();
            });
            Logger::log(
                'Restore Flash Sale',
                $flashSale,
                "Đã khôi phục chiến dịch Flash Sale: {$flashSale->name}"
            );
            return back()->with('success', 'Flash Sale đã được khôi phục thành công!');

        } catch (\Exception $e) {
            return back()->with('error', 'Không thể khôi phục: ' . $e->getMessage());
        }
    }
    public function forceDelete($id)
    {
        try {
            $flashSale = FlashSale::withTrashed()->findOrFail($id);
            $flashSaleName = $flashSale->name;
            $hasSales = $flashSale->items()->withTrashed()->where('sold_quantity', '>', 0)->exists();
            $hasOrders = $flashSale->orderDetails()->exists();
            if ($hasSales || $hasOrders) {
                return back()->with('error', 'Flash Sale này đã có sản phẩm được bán ra, không thể xóa vĩnh viễn!');
            }
            DB::transaction(function () use ($flashSale, $id, $flashSaleName) {
                $flashSale->items()->withTrashed()->forceDelete();

                $flashSale->forceDelete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Flash Sale',
                    'model_type' => FlashSale::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn Flash Sale: {$flashSaleName}",
                    'ip_address' => request()->ip(),
                    'properties' => json_encode([
                        'name' => $flashSaleName,
                        'deleted_at' => now()->toDateTimeString()
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
            return back()->with('success', 'Đã xóa vĩnh viễn Flash Sale thành công!');

        }catch (\Exception $e) {
            return back()->with('error', 'Có lỗi hệ thống xảy ra: ' . $e->getMessage());
        }
    }
}
