<?php

namespace App\Http\Controllers\admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class ProductTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:product-types.view', only: ['index', 'show']),
            new Middleware('can:product-types.create', only: ['create', 'store']),
            new Middleware('can:product-types.edit', only: ['edit', 'update']),
            new Middleware('can:product-types.delete', only: ['destroy', 'restore', 'forceDelete']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status', 'active');
        $searchTerm = $request->input('search');

        $query = ProductType::query();

        if ($status === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where('type_name', 'ilike', '%' . $searchTerm . '%');
        }

        $productTypes = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/ProductTypes/Index', [
            'productTypes' => $productTypes,
            'filters' => [
                'search' => $searchTerm,
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
        return Inertia::render('admin/ProductTypes/Create',[]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
        'type_name'      => 'required|string|max:255',
        ]);

        try{
            DB::transaction(function () use($request){
                $productType = ProductType::create([
                    'type_name' => $request->type_name,
                ]);
                Cache::forget('menu_categories_data');
                Logger::log(
                    'Create Product Type',
                    $productType,
                    "Đã tạo loại sản phẩm mới: {$productType->type_name}"
                );
            });
           return redirect()->route('admin.producttypes.index')->with('success','Loại sản phẩm đã được thêm thành công!');
        }catch(\Exception $e){
            return back()->with(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductType $productType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductType $producttype)
    {
        return Inertia::render('admin/ProductTypes/Edit', [
        'productType' => $producttype
    ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductType $producttype)
    {
        $request->validate([
            'type_name'      => 'required|string|max:255',
        ]);

        try{
            DB::transaction(function () use ($request, $producttype) {
                $oldName = $producttype->type_name;
                $producttype->update([
                    'type_name' => $request->type_name,
                ]);
                Cache::forget('menu_categories_data');
                Logger::log(
                    'Update Product Type',
                    $producttype,
                    "Đã cập nhật loại sản phẩm: từ '{$oldName}' thành '{$producttype->type_name}'",
                    [
                        'old_name' => $oldName,
                        'new_name' => $producttype->type_name
                    ]
                );
            });
            return redirect()->route('admin.producttypes.index')->with('success', 'Cập nhật loại sản phẩm thành công!');
        }catch(\Exception $e){
            return back()->withInput()->with('error', 'Lỗi cập nhật: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductType $producttype)
    {
        if ($producttype->categories()->withTrashed()->exists()) {
            return back()->with('error', "Không thể xóa! Loại sản phẩm '{$producttype->type_name}' đang được gắn với các danh mục sản phẩm.");
        }

        try {
            $typeName = $producttype->type_name;
            $producttype->delete();
            Cache::forget('menu_categories_data');
            Logger::log(
                'Soft Delete Product Type',
                $producttype,
                "Đã tạm xóa loại sản phẩm: {$typeName}"
            );

            return redirect()->route('admin.producttypes.index')->with('success', 'Loại sản phẩm đã được chuyển vào thùng rác!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $productType = ProductType::withTrashed()->findOrFail($id);
            $productType->restore();
            Cache::forget('menu_categories_data');
            Logger::log(
                'Restore Product Type',
                $productType,
                "Đã khôi phục loại sản phẩm: {$productType->type_name}"
            );

            return back()->with('success', "Đã khôi phục loại sản phẩm thành công!");
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể khôi phục: ' . $e->getMessage());
        }
    }

    public function forceDelete($id)
    {
        try {
            $productType = ProductType::withTrashed()->findOrFail($id);

            if ($productType->categories()->withTrashed()->exists()) {
                return back()->with('error', 'Không thể xóa vĩnh viễn! Vẫn còn danh mục thuộc loại sản phẩm này.');
            }

            $typeName = $productType->type_name;

            DB::transaction(function () use ($productType, $id, $typeName) {
                $productType->forceDelete();
                Cache::forget('menu_categories_data');
                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Product Type',
                    'model_type' => ProductType::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn loại sản phẩm: {$typeName}",
                    'properties' => json_encode(['type_name' => $typeName]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', 'Đã xóa vĩnh viễn loại sản phẩm khỏi hệ thống!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xóa vĩnh viễn: ' . $e->getMessage());
        }
    }
}
