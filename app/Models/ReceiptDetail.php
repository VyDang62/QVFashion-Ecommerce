<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ReceiptDetail extends Model
{
    protected $fillable = [
        'receipt_id','product_variant_id','received_quantity',
        'purchase_price','sub_total'
    ];

    public function receipt(): BelongsTo {
        return $this->belongsTo(GoodsReceipt::class, 'receipt_id');
    }

    public function variant(): BelongsTo {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
