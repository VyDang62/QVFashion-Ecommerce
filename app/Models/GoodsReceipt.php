<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class GoodsReceipt extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'receipt_date', 'supplier_id', 'user_id', 
        'total_cost', 'receipt_status'
    ];

    public function supplier(): BelongsTo {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany {
        return $this->hasMany(ReceiptDetail::class, 'receipt_id');
    }

    public function batches(): HasMany {
        return $this->hasMany(Batch::class);
    }

    protected static function booted()
{
    static::creating(function ($receipt) {
        $date = now()->format('Ymd');
        $lastReceipt = static::whereDate('created_at', now()->toDateString())
                             ->latest('id')
                             ->first();

        $number = $lastReceipt ? (int) substr($lastReceipt->receipt_code, -4) + 1 : 1;
        
        $receipt->receipt_code = 'PN-' . $date . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    });
}
}
