<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{   
    protected $table = 'batches';
    protected $fillable = [
        'product_variant_id', 'goods_receipt_id', 'batch_code', 
        'purchase_price', 'original_quantity', 
        'remaining_quantity', 'received_date'
    ];

    public function variant() { 
        return $this->belongsTo(ProductVariant::class, 'product_variant_id'); 
    }

    public function receipt() { return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id'); }
}
