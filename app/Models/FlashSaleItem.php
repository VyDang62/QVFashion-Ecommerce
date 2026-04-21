<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlashSaleItem extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $appends = ['discount_percentage'];
    protected $fillable = [
        'flash_sale_id', 'product_variant_id',
        'sale_price', 'sale_quantity',
        'sold_quantity', 'user_limit'
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getDiscountPercentageAttribute()
    {
        $originalPrice = $this->variant->price;
        if($originalPrice <= 0) return 0;

        return round((($originalPrice - $this->sale_price) / $originalPrice) * 100);
    }
}
