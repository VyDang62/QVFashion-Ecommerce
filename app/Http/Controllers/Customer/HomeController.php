<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Product;
use Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $productQuery = Product::query()
            ->select('id', 'product_name', 'slug', 'category_id')
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
            ->when(Auth::check(), function($q) {
                    $q->withExists(['wishlist as is_favorited' => function($sub) {
                        $sub->where('user_id', Auth::id());
                    }]);
                })
        ->active();
        $popularProducts = (clone $productQuery)->orderByDesc('order_count')->take(4)->get();
        $latestProducts = (clone $productQuery)->latest()->take(4)->get();

        $banners = Banner::where('is_active', true)
            ->where('position', 'home_slider')
            ->orderBy('order')
            ->limit(5)
            ->get(['title', 'subtitle', 'image_path', 'link_url']);

        return Inertia::render('customer/Index', [
            'popularProducts' => $popularProducts->map(fn($p) => $this->formatProduct($p)),
            'latestProducts' => $latestProducts->map(fn($p) => $this->formatProduct($p)),
            'banners' => $banners->map(fn($b) => [
                'title' => $b->title,
                'desc' => $b->subtitle,
                'image' => asset('storage/' . $b->image_path),
                'link' => $b->link_url
            ]),
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
            'active_flash_sales' => $flashSaleData,
            'is_favorited'  => (bool) ($product->is_favorited ?? false),
        ];
    }
    
}
