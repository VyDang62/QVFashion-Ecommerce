<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show($slug)
    {
        $product = Product::query()
            ->select(['id','product_name','slug','product_description','brand_id','category_id'])
            ->where('slug',$slug)
            ->with([
                'brand:id,brand_name',
                'category:id,category_name,category_slug',

                'images:id,product_id,image_path,is_primary',

                'variants:id,product_id,sku,price,stock_quantity',

                'variants.attributeValues:id,attribute_id,value,hex_code',
                'variants.attributeValues.attribute:id,attribute_name',

                'variants.flashSaleItems' => function($query) {
                    $query->whereHas('flashSale', function($q) {
                        $q->current();
                    })
                    ->with('flashSale') 
                    ->whereColumn('sold_quantity', '<', 'sale_quantity');
                },

                'ratings' => function($query) {
                    $query->select('id', 'product_id', 'user_id', 'score', 'content', 'created_at')
                        ->where('is_approved', true)
                        ->latest()
                        ->with('user:id,full_name');
                }
            ])
            ->firstOrFail();

        $ratingsCount = $product->ratings->count();
        $averageRating = $ratingsCount > 0 ? round($product->ratings->avg('score'), 1) : 0;
        
        $product->variants->each(function ($variant) {
            $activeSale = $variant->flashSaleItems->first();
            $variant->sale_price = $activeSale ? $activeSale->sale_price : null;
            $variant->discount = $activeSale ? $activeSale->discount_percentage : 0;
        });
        
        $viewedProducts = session()->get('viewed_products',[]);
        if(!in_array($product->id,$viewedProducts)){
            $product->increment('view_count');
            session()->push('viewed_products',$product->id);
        }
        
        $selectableAttributes = $product->variants->flatMap->attributeValues
            ->groupBy('attribute.attribute_name')
            ->map(function ($values) {
                return $values->unique('id')->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'value' => $v->value,
                        'hex_code' => $v->hex_code
                    ];
                })->values();
            });

        $relatedProducts = Product::select('id', 'product_name', 'slug', 'category_id')
            ->with([
                'category:id,category_name', 
                'images:id,product_id,image_path,is_primary',
                'variants:id,product_id,price',
                'activeFlashSales' => function($query) {
                    $query->select('flash_sale_items.*')
                        ->with('variant:id,price')
                        ->orderBy('sale_price', 'asc');
                }
            ])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->when(Auth::check(), function($q) {
                    $q->withExists(['wishlist as is_favorited' => function($sub) {
                        $sub->where('user_id', Auth::id());
                    }]);
                })
            ->where('is_active', true)
            ->latest()
            ->take(4)
            ->get();


        //Meta data
        $primaryImage = $product->images->where('is_primary',true)->first() ?? $product->images->first();
        $metaImage = $primaryImage ? asset('storage/' . $primaryImage->image_path) : asset('assets/images/placeholder.jpg');

        $plainDescription = strip_tags($product->product_description);
        $metaDescription = Str::limit($plainDescription,160);

        $minPrice = $product->variants->min('price');
        return Inertia::render('customer/ProductDetail', [
            'product' => $product,
            'attributes' => $selectableAttributes,
            'averageRating' => $averageRating,
            'ratingsCount' => $ratingsCount,
            'relatedProducts' => $relatedProducts->map(fn($p) => $this->formatProduct($p)),
            'seo' => [
                'title' => $product->product_name . ' - ' . ($product->brand->brand_name ?? 'Shop Thời Trang'),
                'description' => $metaDescription,
                'image' => $metaImage,
                'url' => URL::current(),
                'type' => 'product',
                'product' => [
                    'price_amount' => $minPrice,
                    'price_currency' => 'VND',
                    'availability' => $product->variants->sum('stock_quantity') > 0 ? 'instock' : 'oos',
                    'brand' => $product->brand->brand_name ?? '',
                    'category' => $product->category->category_name ?? '',
                ],
                'schema' => [
                    '@context' => 'https://schema.org/',
                    '@type' => 'Product',
                    'name' => $product->product_name,
                    'image' => $metaImage,
                    'description' => $metaDescription,
                    'sku' => $product->variants->first()->sku ?? '',
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => $product->brand->brand_name ?? 'Generic'
                    ],
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => URL::current(),
                        'priceCurrency' => 'VND',
                        'price' => $minPrice,
                        'availability' => 'https://schema.org/InStock',
                    ]
                ]
            ],
            'isFavorited' => auth()->check() 
            ? Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->exists() 
            : false,
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
            'price' => $price,
            'old_price' => $oldPrice,
            'image' => $primaryImage ? asset('storage/' . $primaryImage->image_path) : '/assets/images/placeholder.jpg',
            'active_flash_sales' => $flashSaleData,
            'is_favorited'  => (bool) ($product->is_favorited ?? false),
        ];
    }

    public function store(Request $request, $productId)
    {
        $validate = $request->validate([
            'score'   => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:1|max:500',
        ],[
            'content' => 'Nhận xét phải ít hơn 500 ký tự!'
        ]);

        $product = Product::findOrFail($productId);

        // $existingReview = Rating::where('product_id', $productId)
        //                         ->where('user_id', Auth::id())
        //                         ->first();
        // if ($existingReview) {
        //     return back()->with(['error' => 'Bạn đã đánh giá sản phẩm này rồi.']);
        // }

        try {
            Rating::create([
                'product_id'  => $productId,
                'user_id'     => Auth::id(),
                'score'       => $validate['score'],
                'content'     => $validate['content'],
                'is_approved' => true,
            ]);

            return back()->with('success', 'Đánh giá của bạn đã được gửi thành công!');

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
