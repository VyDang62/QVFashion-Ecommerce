<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ProductVariant extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = ['product_id', 'sku', 'price', 'low_stock_threshold', 'stock_quantity'];
    protected $appends = ['primary_thumbnail'];
    public function product() { 
        return $this->belongsTo(Product::class); 
    }
    public function attributeValues(){
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values', 'variant_id', 'attribute_value_id')
                    ->withTimestamps();
    }

    public function images(){
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function batches() { 
        return $this->hasMany(Batch::class); 
    }

    public function orderDetails(){
        return $this->hasMany(OrderDetail::class);
    }

    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function getCurrentFlashSaleAttribute()
    {
        return $this->flashSaleItems()
        ->whereHas('flashSale', function($query) {
            $query->current();
        })
        ->first();
    }

    public function getPrimaryThumbnailAttribute()
    {
        //Tìm ảnh chính của biến thể này
        $variantImage = $this->images()->where('is_primary', true)->first();
        if ($variantImage) return asset('storage/' . $variantImage->image_path);

        //Nếu không có ảnh chính, lấy ảnh bất kỳ của biến thể
        $anyVariantImage = $this->images()->first();
        if ($anyVariantImage) return asset('storage/' . $anyVariantImage->image_path);

        //Nếu biến thể không có ảnh, lấy ảnh chính của sản phẩm cha
        $productImage = ProductImage::where('product_id', $this->product_id)
            ->where('is_primary', true)
            ->whereNull('variant_id') // Ảnh chung của sản phẩm
            ->first();

        if ($productImage) return asset('storage/' . $productImage->image_path);

        return asset('images/default-thumbnail.png');
    }
}
