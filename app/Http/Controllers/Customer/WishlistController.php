<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlistItems = Wishlist::where('user_id', Auth::id())
            ->with([
                'product' => function($q) {
                    $q->select('id', 'product_name', 'slug', 'category_id')
                      ->with([
                          'category:id,category_name',
                          'images:id,product_id,image_path,is_primary',
                          'variants:id,product_id,price',
                          'activeFlashSales' => function($query) {
                              $query->select('flash_sale_items.*')
                                    ->with('variant:id,price')
                                    ->orderBy('sale_price', 'asc');
                          }
                      ]);
                }
            ])
            ->latest()
            ->get();

        return Inertia::render('customer/Wishlist', [
            'wishlistItems' => $wishlistItems->map(fn($item) => $this->formatWishlistItem($item))
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => [
                    'required',
                    'integer',
                    Rule::exists('products', 'id')->where(function ($query) {
                        $query->where('is_active', true);
                    }),
                ],
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        try{
            $exists = Wishlist::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if($exists){
                $exists->delete();
                return back()->with('success','Đã xóa sản phẩm khỏi danh sách yêu thích!');
            }

            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);

            return back()->with('success', 'Đã thêm sản phẩm vào danh sách yêu thích!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Không thể thực hiện thao tác: ' . $e->getMessage()]);
        }
    }

    private function formatWishlistItem($item)
    {
        $product = $item->product;
        
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
            'wishlist_id' => $item->id,
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'category' => $product->category->category_name ?? 'Thời trang',
            'image' => $primaryImage ? asset('storage/' . $primaryImage->image_path) : '/assets/images/placeholder.jpg',
            'price' => $price, 
            'old_price' => $oldPrice, 
            'active_flash_sales' => $flashSaleData,
            'is_favorited' => true,
        ];
    }
}
