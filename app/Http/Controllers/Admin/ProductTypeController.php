<?php

namespace App\Http\Controllers\admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;
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

            new Middleware('can:product-types.delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage',10);
        $query = ProductType::query();
        if ($request->filled('search')){
            $query->where('type_name','ilike','%'.$request->search.'%');
        }

        $productTypes = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/ProductTypes/Index',[
            'productTypes' => $productTypes,
            'filters' => $request->only(['search','perPage'])
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
        if ($producttype->categories()->exists()) {
            return back()->with('error', "Không thể xóa! Loại sản phẩm '{$producttype->type_name}' đang được sử dụng bởi các danh mục!");
        }

        try {
            $typeName = $producttype->type_name;
            $typeId = $producttype->id;

            return DB::transaction(function () use ($producttype, $typeName, $typeId) {
                
                $producttype->delete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Delete Product Type',
                    'model_type' => ProductType::class,
                    'model_id' => $typeId,
                    'description' => "Đã xóa vĩnh viễn loại sản phẩm: {$typeName}",
                    'properties' => json_encode(['type_name' => $typeName]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);

                return redirect()->route('admin.producttypes.index')->with('success', 'Loại sản phẩm đã được xóa thành công!');
            });

        } catch (QueryException $e) {
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
