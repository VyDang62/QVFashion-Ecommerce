<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VoucherType;
use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class VoucherController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:vouchers.view', only: ['index', 'show']),

            new Middleware('can:vouchers.create', only: ['create', 'store']),

            new Middleware('can:vouchers.edit', only: ['edit', 'update']),

            new Middleware('can:vouchers.delete', only: ['destroy', 'restore', 'forceDelete']),
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

        $query = ($status === 'trash') ? Voucher::onlyTrashed() : Voucher::query();

        if($request->filled('search')){
            $query->where('code','ilike','%'.strtoupper($search).'%');
        }

        $vouchers = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Vouchers/Index',[
            'vouchers' => $vouchers,
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
        return Inertia::render('admin/Vouchers/Create', [
           'voucherTypes' => VoucherType::toSelectOptions(),
           'brands' => Brand::all(),
           'categories' => Category::GetOnlyChildren()->get(),
           'products' => Product::active()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'code' => 'required|string|unique:vouchers,code|max:50',
            'voucher_type' => ['required', new Enum(VoucherType::class)],
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'required|integer|min:1',
            'start_date' => 'nullable|date|after:now',
            'end_date'  => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
            'brand_ids'           => 'nullable|array',
            'brand_ids.*'         => 'exists:brands,id',
            'category_ids'        => 'nullable|array',
            'category_ids.*'      => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ],[
            'start_date.after' => 'Thời gian bắt đầu phải sau thời điểm hiện tại!',
            'end_date.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu!',
        ]);

        $validate['code'] = strtoupper($validate['code']);

        try{
            DB::transaction(function () use ($request, $validate){
                $voucher = Voucher::create($validate);

                $restrictions = collect();

                if($request->filled('brand_ids')){
                    foreach($request->brand_ids as $id){
                        $restrictions->push(['restrict_type' => 'brand', 'restrict_id' => $id]);
                    }
                }

                if ($request->filled('category_ids')) {
                    foreach ($request->category_ids as $id) {
                        $restrictions->push(['restrict_type' => 'category', 'restrict_id' => $id]);
                    }
                }
                
                if($request->filled('product_ids')){
                    foreach($request->product_ids as $id){
                        $restrictions->push(['restrict_type' => 'product','restrict_id' => $id]);
                    }
                }

                if($restrictions->isNotEmpty()){
                    $voucher->restrictions()->createMany($restrictions->toArray());
                }

                Logger::log(
                    'Create Voucher',
                    $voucher,
                    "Đã tạo mã giảm giá mới: {$voucher->code}",
                    [
                        'type' => $voucher->voucher_type,
                        'value' => $voucher->discount_value,
                        'usage_limit' => $voucher->usage_limit,
                        'restrictions_count' => $restrictions->count()
                    ]
                );
                
            });
            return redirect()->route('admin.vouchers.index')->with('success','Tạo voucher thành công!');
        }catch(\Exception $e){
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Voucher $voucher)
    {
        $voucher->load(['restrictions','usages' => function($query){
            $query->with('user')->latest('used_at')->limit(10);
        }]);

        $stats = [
            'total_discount_amount' => $voucher->usages()->sum('discount_amount'),
        ];
        return Inertia::render('admin/Vouchers/Show',[
            'voucher' => $voucher,
            'stats'   => $stats
        ]); 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        $voucher->load('restrictions');

        return Inertia::render('admin/Vouchers/Edit',[
            'voucher' => $voucher,
            'voucherTypes' => VoucherType::toSelectOptions(),
            'brands' => Brand::all(),
            'categories' => Category::getOnlyChildren()->get(),
            'products' => Product::active()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        $validate = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,'.$voucher->id,
            'voucher_type' => ['required', new Enum(VoucherType::class)],
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date'  => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'brand_ids' => 'nullable|array',
            'brand_ids.*'   => 'exists:brands,id',
            'category_ids'  => 'nullable|array',
            'category_ids.*'    => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $validate['code'] = strtoupper($validate['code']);

        try{
            DB::transaction(function () use ($request, $voucher, $validate){
                $oldData = $voucher->only(['code', 'discount_value', 'is_active', 'usage_limit']);
                $voucher->update($validate);

                $voucher->restrictions()->delete();
                $newRestrictions = collect();

                if($request->filled('brand_ids')){
                    foreach($request->brand_ids as $id){
                        $newRestrictions->push(['restrict_type' => 'brand', 'restrict_id' => $id]);
                    }
                }

                if ($request->filled('category_ids')) {
                    foreach ($request->category_ids as $id) {
                        $newRestrictions->push(['restrict_type' => 'category', 'restrict_id' => $id]);
                    }
                }
                
                if($request->filled('product_ids')){
                    foreach($request->product_ids as $id){
                        $newRestrictions->push(['restrict_type' => 'product','restrict_id' => $id]);
                    }
                }
                if($newRestrictions->isNotEmpty()){
                    $voucher->restrictions()->createMany($newRestrictions->toArray());
                }

                Logger::log(
                    'Update Voucher',
                    $voucher,
                    "Đã cập nhật mã giảm giá: {$voucher->code}",
                    [
                        'old' => $oldData,
                        'new' => $voucher->only(['code', 'discount_value', 'is_active', 'usage_limit'])
                    ]
                );
                
            });
            return redirect()->route('admin.vouchers.index')->with('success','Tạo voucher thành công!');
        }catch(\Exception $e){
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        $voucherCode = $voucher->code;
        $voucher->delete();
        Logger::log(
            'Soft Delete Voucher',
            $voucher,
            "Đã tạm xóa mã giảm giá: {$voucherCode}"
        );
        return back()->with('success', 'Mã giảm giá đã được tạm xóa!');
    }
    
    public function restore($id)
    {
        $voucher = Voucher::onlyTrashed()->findOrFail($id);
        $voucher->restore();
        Logger::log(
            'Restore Voucher',
            $voucher,
            "Đã khôi phục mã giảm giá: {$voucher->code}"
        );
        return back()->with('success','Khôi phục mã giảm giá thành công!');
    }

    public function forceDelete($id)
    {
        try {
            $voucher = Voucher::onlyTrashed()->findOrFail($id);

            $hasOrders = $voucher->usages()->exists() || $voucher->orders()->exists();
            if ($hasOrders) {
                return back()->with('error', 'Không thể xóa vĩnh viễn mã giảm giá này vì đã có đơn hàng sử dụng trong lịch sử!');
            }
            $voucherCode = $voucher->code;
            DB::transaction(function () use ($voucher, $id, $voucherCode) {

                $voucher->forceDelete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Voucher',
                    'model_type' => Voucher::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn mã giảm giá: {$voucherCode}",
                    'properties' => json_encode(['code' => $voucherCode]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', 'Đã xóa vĩnh viễn mã giảm giá khỏi hệ thống!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi xóa vĩnh viễn: ' . $e->getMessage());
        }
    }
}
