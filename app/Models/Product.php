<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'product_name', 'sku', 'product_description', 'slug',
        'brand_id', 'category_id', 'is_active', 'is_featured','meta_title','meta_description','meta_keywords'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
    public function brand() { return $this->belongsTo(Brand::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function images() {
        return $this->hasMany(ProductImage::class, 'product_id');
    }
    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }
    public function ratings(){
        return $this->hasMany(Rating::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active',true);
    }

    public function orderDetails(){
        return $this->hasManyThrough(
            OrderDetail::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id'
        );
    }

    public function flashSaleItems()
    {
        return $this->hasManyThrough(FlashSaleItem::class, ProductVariant::class, 'product_id','product_variant_id');
    }

    public function activeFlashSales()
    {
        return $this->hasManyThrough(
            FlashSaleItem::class, 
            ProductVariant::class, 
            'product_id', 
            'product_variant_id',
            'id',
            'id'
        )
        ->whereHas('flashSale', function ($query) {
            $query->current();
        })
        ->whereColumn('flash_sale_items.sold_quantity', '<', 'flash_sale_items.sale_quantity');
    }
    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->product_name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('product_name')) {
                $product->slug = static::generateUniqueSlug($product->product_name, $product->id);
            }
        });
    }

    private static function generateUniqueSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $i = 1;
        while (static::where('slug', $slug)
                    ->when($excludeId, function($query, $id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }

        return $slug;
    }
}
