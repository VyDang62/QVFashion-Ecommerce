<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderDetail extends Model
{
    protected $fillable = ['order_id', 'product_variant_id', 'flash_sale_id', 'product_name','variant_info','original_price','unit_price', 'quantity', 'sub_total'];
    public function order(): BelongsTo {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(){
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    public function getIsFlashSaleAttribute()
    {
        return !is_null($this->flash_sale_id);
    }
}
