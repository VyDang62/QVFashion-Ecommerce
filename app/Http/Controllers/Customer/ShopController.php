<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request, $slug = null)
    {
        $perPage = cache()->rememberForever('settings.items_per_page', function() {
            return Setting::where('key', 'items_per_page')->value('value') ?? 12;
        });
        //Lấy dữ liệu
        $brands = Brand::select('id', 'brand_name as name')
            ->orderBy('brand_name')
            ->get();
        
        $availableAttributes = Attribute::with(['values' => function($q) {
            $q->select('id', 'attribute_id', 'value as name', 'hex_code');
        }])->get();

        $priceOptions = [
            ['id' => '0-6500000',           'name' => 'Dưới 6.5 triệu'],
            ['id' => '6500000-13000000',    'name' => '6.5 triệu - 13 triệu'],
            ['id' => '13000000-26000000',   'name' => '13 triệu - 26 triệu'],
            ['id' => '26000000-52000000',   'name' => '26 triệu - 52 triệu'],
            ['id' => '52000000-130000000',  'name' => '52 triệu - 130 triệu'],
            ['id' => '130000000-260000000', 'name' => '130 triệu - 260 triệu'],
            ['id' => '260000000-520000000', 'name' => '260 triệu - 520 triệu'],
            ['id' => '520000000-999999999', 'name' => 'Trên 520 triệu'],
        ];

        $sortOptions = [
            ['id' => 'latest',     'name' => 'Mới nhất'],
            ['id' => 'price_asc',  'name' => 'Giá: Thấp đến Cao'],
            ['id' => 'price_desc', 'name' => 'Giá: Cao đến Thấp'],
            ['id' => 'popular',    'name' => 'Bán chạy nhất'],
        ];
        //Tạo query
        $query = Product::query()
                ->select('id','product_name','slug','category_id','brand_id','created_at')
                ->with([
                    'category.productType', 
                    'images:id,product_id,image_path,is_primary', 
                    'variants:id,product_id,price',
                    'activeFlashSales' => function($q) {
                        $q->select('flash_sale_items.*')
                        ->with('variant:id,price')
                        ->orderBy('sale_price', 'asc');
                    }
                ])
                ->when(Auth::check(), function($q) {
                    $q->withExists(['wishlist as is_favorited' => function($sub) {
                        $sub->where('user_id', Auth::id());
                    }]);
                })
                ->active();
        
        //Lọc theo search keyword
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            
            $query->where(function ($q) use ($searchTerm) {
                $q->where('product_name', 'ilike', "%{$searchTerm}%")
                ->orWhere('slug', 'ilike', "%{$searchTerm}%")
                ->orWhere('product_description', 'ilike', "%{$searchTerm}%")

                ->orWhereHas('variants', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('sku', 'ilike', "%{$searchTerm}%");
                });
            });
        }

        //Lọc Flash Sale
        if ($request->boolean('is_flash_sale')) {
            $query->whereHas('activeFlashSales');
        }

        //Lọc theo giá
        if($request->filled('prices')){
            $prices = is_array($request->prices) ? $request->prices : [$request->prices];

            $query->whereHas('variants', function($q) use ($prices){
                $q->where(function ($subQuery) use ($prices){
                    foreach ($prices as $range){
                        $parts = explode('-',$range);
                        if(count($parts) === 2){
                            $min = (int)$parts[0];
                            $max = (int)$parts[1];

                            $subQuery->orWhereBetween('price',[$min,$max]);
                        }
                    }
                });
            });
        }
        //Sắp xếp
        $sort = $request->get('sort', 'latest');

        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $direction = ($sort === 'price_asc') ? 'asc' : 'desc';
            $query->addSelect(['min_price' => ProductVariant::selectRaw('min(price)')
                ->whereColumn('product_id', 'products.id')
            ])->orderBy('min_price', $direction);
        } elseif ($sort === 'popular') {
            $query->withCount('orderDetails')->orderBy('order_details_count', 'desc');
        } else {
            $query->latest();
        }
        //Lọc theo danh mục
        if ($slug) {
            $validGenders = ['male', 'female']; 
            if (in_array($slug, $validGenders)) {
                $request->merge(['gender' => [$slug]]);

                $productTypeIds = Category::where('gender', $slug)
                    ->whereNotNull('product_type_id')
                    ->distinct()
                    ->pluck('product_type_id')
                    ->map(fn($id) => (string)$id)
                    ->toArray();

                $childCategoryIds = Category::where('gender',$slug)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id)
                    ->toArray();
                
                $request->merge([
                    'product_types' => array_unique(array_merge((array)$request->product_types, $productTypeIds)),
                    'categories' => array_unique(array_merge((array)$request->categories, $childCategoryIds))
                    ]);
            } 
            
            elseif ($productType = ProductType::where('slug', $slug)->first()) {
                $request->merge(['product_types' => [$productType->id]]);

                $childCategoryIds = Category::where('product_type_id', $productType->id)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id)
                    ->toArray();

                $request->merge(['categories' => array_unique(array_merge((array)$request->categories, $childCategoryIds))]);
            }

            elseif ($currentCat = Category::where('category_slug', $slug)->first()) {
                $request->merge(['categories' => array_unique(array_merge((array)$request->categories, [$currentCat->id]))]);
            }
        }
        if ($request->filled('gender')) {
            $genders = is_array($request->gender) ? $request->gender : [$request->gender];
            $query->whereHas('category', function ($q) use ($genders) {
                $q->whereIn('gender', $genders);
            });
        }

        if ($request->filled('product_types')) {
            $typeIds = is_array($request->product_types) ? $request->product_types : [$request->product_types];
            $query->whereHas('category', function ($q) use ($typeIds) {
                $q->whereIn('product_type_id', $typeIds);
            });
        }

        if ($request->filled('categories')) {
            $inputCategoryIds = (array) $request->categories;
                $targetCategoryIds = Category::whereIn('id', $inputCategoryIds)
                    ->orWhereIn('parent_id', $inputCategoryIds)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id)
                    ->toArray();

                $request->merge(['categories' => $targetCategoryIds]);
                
                $query->whereIn('category_id', $targetCategoryIds);
        }
        //Lọc thương hiệu
        if($request->filled('brands')){
            $brandIds = is_array($request->brands) ? $request->brands : [$request->brands];
            $query->whereIn('brand_id',$brandIds);
        }
        //Lọc theo thuộc tính
        if ($request->filled('attribute_values')) {
            //Chuẩn hóa dữ liệu đầu vào luôn là một mảng
            $valueIds = is_array($request->attribute_values) ? $request->attribute_values : [$request->attribute_values];
            //Nhóm giá trị thuộc tính theo 'attribute_id' (Ví dụ: Nhóm Size: [M, L], Nhóm Màu: [Đỏ, Xanh])
            $groupedValueIds = AttributeValue::whereIn('id', $valueIds)
                ->get()
                ->groupBy('attribute_id');

            foreach ($groupedValueIds as $attrId => $ids) {
                $idsCollection = collect($ids); 
                $currentValueIds = $idsCollection->pluck('id')->toArray();
                //Áp dụng logic lọc VÀ (AND) giữa các nhóm thuộc tính khác nhau
                //Ví dụ: Nếu chọn Size M và Màu Đỏ thì sản phẩm phải vừa có Size M và màu Đỏ
                $query->whereHas('variants.attributeValues', function($q) use ($currentValueIds) {
                    //Trong cùng một nhóm (VD: Size), áp dụng logic lọc HOẶC (OR)
                    //Ví dụ: Nếu chọn Size M và Size L thì Lấy sản phẩm có Size M HOẶC Size L.
                    $q->whereIn('attribute_values.id', $currentValueIds);
                });
            }
        }

        $products = $query->paginate((int)$perPage)
            ->withQueryString()
            ->through(fn($product) => $this->formatProduct($product));

        return Inertia::render('customer/Shop', [
            'products' => $products,
            'filters' => $request->only(['gender', 'product_types', 'sort', 'categories', 'brands', 'attribute_values', 'prices', 'search', 'is_flash_sale']),
            'brands' => $brands,
            'attributes' => $availableAttributes,
            'priceOptions' => $priceOptions, 
            'sortOptions' => $sortOptions,
        ]);
    }

    private function formatProduct($product)
    {
        $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
    
        $flashSale = $product->activeFlashSales->first();
        
        $minVariantPrice = (float) $product->variants->min('price');

        if ($flashSale) {
            $price = (float) $flashSale->sale_price;
            $oldPrice = $minVariantPrice;
            $flashSaleData = [
                [
                    'sale_price' => $price,
                    'sale_quantity' => $flashSale->sale_quantity,
                    'sold_quantity' => $flashSale->sold_quantity,
                    'discount_percent' => $flashSale->discount_percentage,
                ]
            ];
        } else {
            $price = $minVariantPrice;
            $oldPrice = null;
            $flashSaleData = [];
        }

        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'category' => $product->category->category_name ?? 'Thời trang',
            'image' => $primaryImage ? asset('storage/' . $primaryImage->image_path) : '/assets/images/placeholder.jpg',
            'price' => $price,
            'old_price' => $oldPrice,
            'is_favorited'  => (bool) ($product->is_favorited ?? false),
            'active_flash_sales' => $flashSaleData,
        ];
    }
}
