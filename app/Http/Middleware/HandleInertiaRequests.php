<?php

namespace App\Http\Middleware;

use App\Enums\Gender;
use App\Models\Category;
use App\Models\Page;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {   
        //logo
        $logoPath = Setting::get('website_logo');
        //category
        // $menuCategories = Cache::rememberForever('menu_categories',function (){
        //    return Category::whereNull('parent_id')
        //         ->with(['productType','children'])
        //         ->get()
        //         ->groupBy('gender')
        //         ->map(function ($categories) {
        //             return $categories->groupBy(function ($cat) {
        //                 return $cat->productType;
        //             });
        //         })
        //         ->toArray();
        // });

        //Lấy Menu
        $menuCategories = Category::whereNull('parent_id')
            ->select('id', 'category_name', 'category_slug', 'gender', 'product_type_id')
            ->with([
                    'productType:id,type_name,slug', 
                    'children' => function($q) {
                        $q->select('id', 'parent_id', 'category_name', 'category_slug');
                    }
                ])
            ->get()
            ->groupBy('gender')
            ->map(function ($categoriesByGender) {
                return $categoriesByGender->groupBy('product_type_id')
                    ->map(function ($items) {
                        $firstItem = $items->first();
                        
                        return [
                            'id' => $firstItem->productType->id,
                            'type_name' => $firstItem->productType->type_name,
                            'type_slug' => $firstItem->productType->slug,
                            'categories' => $items
                        ];
                    })->values();
            })
            ->toArray();

        //Lấy cart
        $user = $request->user();
        $cartItems = [];
        $rawCartItems = [];
        if ($user) {
            $rawCartItems = $user->cartItems()
            ->with(['product.images', 'variant.attributeValues.attribute','variant.flashSaleItems.flashSale'])
            ->get();
        } else {
            $sessionCart = session('cart', []);
            if (!empty($sessionCart)) {
                $variantIds = array_keys($sessionCart);
                
                $variants = ProductVariant::with(['product.images', 'attributeValues.attribute','flashSaleItems.flashSale'])
                    ->whereIn('id', $variantIds)
                    ->get()
                    ->keyBy('id');
                foreach ($sessionCart as $vId => $item) {
                    if (isset($variants[$vId])) {
                        $variant = $variants[$vId];
                        
                        $rawCartItems[] = (object)[
                            'id' => $vId,
                            'product' => $variant->product,
                            'variant' => $variant,
                            'quantity' => $item['quantity'],
                        ];
                    }
                }
            }
        }

        $cartItems = collect($rawCartItems)->map(function ($item) {
            $image = $item->variant->images?->first()?->image_path 
                    ?? $item->product->images->where('is_primary', true)->first()?->image_path 
                    ?? $item->product->images->first()?->image_path;

            $variantInfo = collect($item->variant->attributeValues ?? [])
                ->map(fn($av) => $av->attribute->attribute_name . ': ' . $av->value)
                ->implode(' / ');
            return [
                'id'             => $item->id,
                'name'           => $item->product->product_name,
                'variant_info'   => $variantInfo,
                'image'          => $image ? "/storage/{$image}" : '/assets/images/placeholder.jpg',
                'quantity'       => $item->quantity,
                'price'          => $item->variant->price,
                'current_price'  => $item->current_price ?? $item->variant->price,
                'subtotal'       => $item->subtotal ?? ($item->variant->price * $item->quantity),
                'is_flash_sale'  => $item->is_flash_sale ?? false,
            ];
        });
        
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name') : [],
                'roles' => $request->user() ? $request->user()->getRoleNames() : [],
                'notifications' => $request->user() ? $request->user()->notifications()->latest()->limit(10)->get() : [],
                'unread_count' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            ],
            'settings' => [
                'logo' => asset('storage/' . $logoPath),
                'site_name' => Setting::get('site_name', 'Fashion Ecommerce'),
                'contact_email' => Setting::get('contact_email'),
                'phone_number' => Setting::get('phone_number'),
                'address' => Setting::get('address'),
                'description' => Setting::get('description'),
                'facebook_link' => Setting::get('facebook_link'),
                'twitter_link' => Setting::get('twitter_link'),
                'instagram_link' => Setting::get('instagram_link'),
                'youtube_link' => Setting::get('youtube_link'),
            ],
            'menuCategories' => [
                'data' => $menuCategories,
                'labels' => Gender::labels(),
            ],

            'cartItems' => $cartItems,
            'cartCount' => $cartItems->count(),
            'cartTotal' => $cartItems->sum('subtotal'),

            'footer_pages' => Page::active()->select('title','slug')->get(),

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
