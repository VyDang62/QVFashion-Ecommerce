<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    protected $appends = ['current_price', 'subtotal', 'is_flash_sale'];
    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isAvailable()
    {
        if($this->product->trashed() || !$this->product->is_active)
        {
            return false;   
        }

        if ($this->variant->trashed()) {
            return false;
        }

        if ($this->variant->stock_quantity < $this->quantity) {
            return false;
        }

        return true;
    }

    public function getCurrentPriceAttribute()
    {
        $now = Carbon::now();

        $flashSaleItem = FlashSaleItem::where('product_variant_id',$this->product_variant_id)
            ->whereHas('flashSale',function ($query) use ($now) {
                $query->where('is_active',true)
                      ->where('start_date','<=',$now)
                      ->where('end_date','>=',$now);
            })
            ->first();
        
        if($flashSaleItem && $flashSaleItem->sold_quantity < $flashSaleItem->sale_quantity)
        {
            return $flashSaleItem->sale_price;
        }

        return $this->variant->price;
    }

    public function getIsFlashSaleAttribute()
    {
        return $this->current_price < $this->variant->price;
    }

    public function getSubtotalAttribute()
    {
        $price = $this->current_price;
        return $price * $this->quantity;
    }
}
