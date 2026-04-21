<?php

namespace App\Models;

use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'voucher_type', 'discount_value', 'max_discount_amount',
        'min_order_value', 'usage_limit', 'used_count', 'per_user_limit',
        'start_date', 'end_date', 'is_active'
    ];

    protected $casts = [
        'voucher_type' => VoucherType::class,
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'discount_value' => 'decimal:2',
    ];

    public function scopeValid(Builder $query): void
    {
        $query->where('is_active',true)
              ->where(function ($q){
                $q->whereNull('start_date')->orWhere('start_date', '<=' , now());
              })
              ->where(function ($q){
                $q->whereNull('end_date')->orWhere('end_date','>=',now());
              })
              ->where(function ($q){
                $q->whereNull('usage_limit')->orWhereRaw('used_count < usage_limit');
              });
    }

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function restrictions()
    {
        return $this->hasMany(VoucherRestriction::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
