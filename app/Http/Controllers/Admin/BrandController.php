<?php

namespace App\Http\Controllers\admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class BrandController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:brands.view', only: ['index', 'show']),

            new Middleware('can:brands.create', only: ['create', 'store']),

            new Middleware('can:brands.edit', only: ['edit', 'update']),

            new Middleware('can:brands.delete', only: ['destroy', 'restore', 'forceDelete']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage',10);
        $query = Brand::query();
        if ($request->filled('search')){
            $query->where('brand_name','ilike','%'.$request->search.'%');
        }
        
        $brands = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Brands/Index',[
            'brands' => $brands,
            'filters' => $request->only(['search','perPage'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Brands/Create', [
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
        'brand_name'      => 'required|string|max:255',
        ]);
        $brand = Brand::create($validate);

        Logger::log(
            'Create Brand',
            $brand,
            "Đã thêm thương hiệu mới: {$brand->brand_name}"
        );

        return redirect()->route('admin.brands.index')->with('success','Thương hiệu đã được thêm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand)
    {
        return Inertia::render('admin/Brands/Edit', [
            'brand' => $brand,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validate = $request->validate([
            'brand_name'      => 'required|string|max:255',
        ]);
        $oldName = $brand->brand_name;
        $brand->update($validate);

        Logger::log(
            'Update Brand',
            $brand,
            "Đã cập nhật thương hiệu từ '{$oldName}' thành '{$brand->brand_name}'",
            [
                'old_name' => $oldName,
                'new_name' => $brand->brand_name
            ]
        );
        return redirect()->route('admin.brands.index')->with('success', 'Cập nhật thương hiệu thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return back()->with('error', "Không thể xóa! Thương hiệu '{$brand->brand_name}' đang có sản phẩm kinh doanh.");
        }

        try {
            $brandId = $brand->id;
            $brandName = $brand->brand_name;

            return DB::transaction(function () use ($brand, $brandId, $brandName) {
                $brand->delete();

                DB::table('activity_logs')->insert([
                    'user_id'     => auth()->id(),
                    'action'      => 'Delete Brand',
                    'model_type'  => Brand::class,
                    'model_id'    => $brandId,
                    'description' => "Đã xóa thương hiệu: {$brandName}",
                    'ip_address'  => request()->ip(),
                    'properties'  => json_encode(['brand_name' => $brandName]),
                    'created_at'  => now(),
                ]);
                return redirect()->route('admin.brands.index')->with('success', 'Thương hiệu đã được xóa thành công!');
            });
            
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi xóa: ' . $e->getMessage());
        }
    }
}
