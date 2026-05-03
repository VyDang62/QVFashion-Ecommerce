<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Enums\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class ProductController extends Controller  implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:products.view', only: ['index', 'show']),

            new Middleware('can:products.create', only: ['create', 'store']),

            new Middleware('can:products.edit', only: ['edit', 'update']),

            new Middleware('can:products.delete', only: ['destroy', 'restore', 'forceDelete']),
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

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        $query = Product::with(['category', 'brand', 'variants', 'images' => function($q) {
            $q->where('is_primary', true);
        }]);
        
        if ($status === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('product_name', 'ilike', '%' . $escapedSearch . '%')
                ->orWhereHas('variants', function ($subQ) use ($escapedSearch) {
                    $subQ->where('sku', 'ilike', '%' . $escapedSearch . '%');
                });
            });
        }

        $products = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'status' => $status
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Products/Create', [
            'genders' => Gender::toSelectOptions(),
            'productTypes' => ProductType::all(),
            'categories' => Category::all(),
            'brands' => Brand::all(),
            'attributes' => Attribute::with('values')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'product_name' => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'brand_id'     => 'required|exists:brands,id',
            'thumbnail'    => 'required|image|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'variants'     => 'required|array|min:1',
            'variants.*.sku'   => 'required|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.image' => 'nullable|image|max:2048',
            'variants.*.low_stock_threshold' => 'required|integer|min:0',
            'gender'          => ['required', new Enum(Gender::class)],
        ]);
        try{
            DB::transaction(function () use($request){
                
                $product = Product::create([
                    'product_name' => $request->product_name,
                    'product_description' => $request->description,
                    'brand_id' => $request->brand_id,
                    'category_id' => $request->category_id,
                    'is_active' => $request->is_active,
                    'is_featured' => $request->is_featured,
                    'meta_title' => $request->meta_title ?? $request->product_name,
                    'meta_description' => $request->meta_description,
                    'meta_keywords' => $request->meta_keywords,
                ]);

                $basePath = "uploads/products/{$product->slug}";
                
                if ($request->hasFile('thumbnail')) {
                    //Lưu vào: storage/app/public/uploads/products/{slug}/thumbnail.jpg
                    $thumbnailPath = $request->file('thumbnail')->store($basePath, 'public');
                    
                    $product->images()->create([
                        'image_path' => $thumbnailPath,
                        'is_primary' => true
                    ]);
                }

                foreach ($request->variants as $index => $vData) {
                    $variant = $product->variants()->create([
                        'sku' => $vData['sku'],
                        'price' => $vData['price'],
                        'stock_quantity' => $vData['stock'],
                        'low_stock_threshold' => $vData['low_stock_threshold'],
                    ]);

                    if (!empty($vData['attribute_values'])) {
                        $variant->attributeValues()->attach(array_filter($vData['attribute_values']));
                    }

                    if ($request->hasFile("variants.{$index}.image")) {
                        $variantSkuSlug = Str::slug($vData['sku']);
                        $variantFolder = "{$basePath}/variants/{$variantSkuSlug}";
                        
                        $variantImagePath = $request->file("variants.{$index}.image")->store($variantFolder, 'public');
                        
                        $product->images()->create([
                            'variant_id' => $variant->id,
                            'image_path' => $variantImagePath,
                            'is_primary' => false
                        ]);
                    }
                }
                $productName = $request->product_name;

                Logger::log(
                    'Create Product',
                    $product,
                    "Đã thêm sản phẩm mới: {$productName}",
                    [
                        'category_id' => $request->category_id,
                        'brand_id' => $request->brand_id,
                        'variant_count' => count($request->variants),
                        'is_active' => $request->is_active
                    ]
                );
            });
            return redirect()->route('admin.products.index')->with('success','Sản phẩm đã được thêm thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['category.parent','category.productType','brand','variants.attributeValues.attribute','images']);

        $product->loadCount([
            'ratings as approved_ratings_count' => function ($query) {
                $query->where('is_approved', true);
            }
        ]);
        return Inertia::render('admin/Products/Show',[
            'product' => $product
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['variants.attributeValues', 'images', 'category']);

        return Inertia::render('admin/Products/Edit', [
            'product' => $product,
            'brands'  => Brand::all(),
            'genders' => Gender::toSelectOptions(),
            'productTypes' => ProductType::all(),
            'categories'   => Category::all(), 
            'attributes'   => Attribute::with('values')->get(), 
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'brand_id'     => 'required|exists:brands,id',
            'variants'     => 'required|array|min:1',
            'is_active'    => 'boolean',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.low_stock_threshold' => 'required|integer|min:0',
        ]);

        foreach ($request->variants as $index => $vData) {
            $variantId = $vData['id'] ?? 0;
            $request->validate([
                "variants.{$index}.sku" => "required|string|unique:product_variants,sku,{$variantId},id",
            ]);
        }

        try {
            DB::transaction(function () use ($request, $product) {
                $oldName = $product->product_name;
                //Cập nhật thông tin sản phẩm
                $product->update([
                    'product_name'        => $request->product_name,
                    'product_description' => $request->description,
                    'brand_id'            => $request->brand_id,
                    'category_id'         => $request->category_id,
                    'is_active'           => $request->is_active ?? true,
                ]);

                $basePath = "uploads/products/{$product->slug}";

                //Xử lý Ảnh đại diện mới (nếu có)
                if ($request->hasFile('thumbnail')) {
                    // Xóa ảnh đại diện cũ
                    $oldPrimary = $product->images()->where('is_primary', true)->first();
                    if ($oldPrimary) {
                        Storage::disk('public')->delete($oldPrimary->image_path);
                        $oldPrimary->delete();
                    }
                    // Lưu ảnh mới
                    $path = $request->file('thumbnail')->store($basePath, 'public');
                    $product->images()->create(['image_path' => $path, 'is_primary' => true]);
                }

                //Xóa các ảnh trong bộ sưu tập được yêu cầu xóa
                if (!empty($request->deleted_image_ids)) {
                    $imagesToDelete = $product->images()->whereIn('id', $request->deleted_image_ids)->get();
                    foreach ($imagesToDelete as $img) {
                        Storage::disk('public')->delete($img->image_path);
                        $img->delete();
                    }
                }

                //Lưu thêm ảnh bộ sưu tập mới
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        $path = $file->store($basePath, 'public');
                        $product->images()->create(['image_path' => $path, 'is_primary' => false]);
                    }
                }

                //Đồng bộ biến thể
                $sentVariantIds = collect($request->variants)->pluck('id')->filter()->toArray();
                //Xóa biến thể không còn trong danh sách gửi lên
                $product->variants()->whereNotIn('id', $sentVariantIds)->delete();

                foreach ($request->variants as $index => $vData) {
                    //Cập nhật hoặc Tạo mới
                    $variant = $product->variants()->updateOrCreate(
                        ['id' => $vData['id'] ?? null],
                        [
                            'sku'            => $vData['sku'],
                            'price'          => $vData['price'],
                            'stock_quantity' => $vData['stock'],
                            'low_stock_threshold'=> $vData['low_stock_threshold'],
                        ]
                    );

                    //Đồng bộ bảng trung gian Attribute Values
                    if (!empty($vData['attribute_values'])) {
                        $variant->attributeValues()->sync(array_filter($vData['attribute_values']));
                    }

                    //Xử lý ảnh riêng cho từng biến thể (nếu có upload mới)
                    if ($request->hasFile("variants.{$index}.image")) {
                        //Xóa ảnh cũ của biến thể này nếu có
                        $oldVariantImg = $product->images()->where('variant_id', $variant->id)->first();
                        if ($oldVariantImg) {
                            Storage::disk('public')->delete($oldVariantImg->image_path);
                            $oldVariantImg->delete();
                        }

                        $vSkuSlug = Str::slug($vData['sku']);
                        $vPath = $request->file("variants.{$index}.image")->store("{$basePath}/variants/{$vSkuSlug}", 'public');
                        
                        $product->images()->create([
                            'variant_id' => $variant->id,
                            'image_path' => $vPath,
                            'is_primary' => false
                        ]);
                    }
                }

                Logger::log(
                    'Update Product',
                    $product,
                    "Đã cập nhật thông tin sản phẩm: {$oldName}",
                    [
                        'new_name' => $request->product_name,
                        'new_status' => $request->is_active,
                        'updated_variants' => collect($request->variants)->pluck('sku')
                    ]
                );
            });
            return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            $productName = $product->product_name;
            DB::transaction(function () use ($product) {
                $product->variants()->delete();
                $product->delete();
            });
            Logger::log(
                'Soft Delete Product',
                $product,
                "Đã chuyển sản phẩm vào thùng rác: {$productName}"
            );
            return redirect()->route('admin.products.index')
                ->with('success', 'Sản phẩm và tất cả biến thể đã được chuyển vào thùng rác!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Không thể xóa tạm sản phẩm: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {   try{
            $product = Product::withTrashed()->findOrFail($id);

            DB::transaction(function () use ($product) {
                $product->restore();
                $product->variants()->withTrashed()->restore();
            });
            
            Logger::log(
                'Restore Product',
                $product,
                "Đã khôi phục sản phẩm: {$product->product_name}"
            );

            return back()->with('success', "Đã khôi phục sản phẩm: {$product->product_name} thành công!");
        }catch(\Exception $e){
            return back()->with('error', 'Không thể khôi phục: ' . $e->getMessage());
        }
        
    }

    public function forceDelete($id)
    {
        $product = Product::withTrashed()->with(['variants', 'images'])->findOrFail($id);

        $hasOrders = $product->variants()->whereHas('orderDetails')->exists();
        if ($hasOrders) {
            return back()->with('error', "Không thể xóa vĩnh viễn! Sản phẩm này đã có trong lịch sử đơn hàng!");
        }

        $hasRatings = $product->ratings()->exists(); 
        if ($hasRatings) {
            return back()->with('error', "Không thể xóa vĩnh viễn! Sản phẩm này đã nhận được bình luận và đánh giá từ khách hàng!");
        }
        try {
            $productName = $product->product_name;
            $productSlug = $product->slug;

            DB::transaction(function () use ($product) {
                //Tạo một danh sách các thư mục cần xóa
                $directoriesToDelete = collect();

                foreach ($product->images as $img) {
                    //Lấy đường dẫn thư mục gốc của sản phẩm từ image_path
                    //Ví dụ: image_path là "uploads/products/ao-thun-cu/variants/sku1/hinh.jpg"
                    //Ta cần lấy "uploads/products/ao-thun-cu"
                    $pathParts = explode('/', $img->image_path);
                    if (count($pathParts) >= 3) {
                        $rootProductFolder = $pathParts[0] . '/' . $pathParts[1] . '/' . $pathParts[2];
                        $directoriesToDelete->push($rootProductFolder);
                    }

                    //Xóa file ảnh lẻ
                    if (Storage::disk('public')->exists($img->image_path)) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                }

                //Xóa các thư mục đã thu thập được
                $directoriesToDelete->unique()->each(function ($folder) {
                    if (Storage::disk('public')->exists($folder)) {
                        Storage::disk('public')->deleteDirectory($folder);
                    }
                });

                //Dự phòng: Xóa thư mục theo slug hiện tại (nếu có)
                $currentBasePath = "uploads/products/{$product->slug}";
                if (Storage::disk('public')->exists($currentBasePath)) {
                    Storage::disk('public')->deleteDirectory($currentBasePath);
                }

                $product->images()->delete();
                $product->variants()->forceDelete();
                $product->forceDelete();
            });

            DB::table('activity_logs')->insert([
                'user_id' => auth()->id(),
                'action' => 'Force Delete Product',
                'model_type' => Product::class,
                'model_id' => $id,
                'description' => "Đã xóa vĩnh viễn sản phẩm: {$productName}",
                'properties' => json_encode(['slug' => $productSlug]),
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            return back()->with('success', "Đã xóa vĩnh viễn sản phẩm và dọn sạch dữ liệu ảnh!");
        } catch (\Exception $e) {
            return back()->with('error', "Lỗi hệ thống khi xóa vĩnh viễn: " . $e->getMessage());
        }
    }
}
