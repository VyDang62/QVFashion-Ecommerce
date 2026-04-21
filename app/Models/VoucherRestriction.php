<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherRestriction extends Model
{
    protected $fillable = ['voucher_id', 'restrict_type', 'restrict_id'];

    public function voucher() { return $this->belongsTo(Voucher::class); }

    public function restrictable()
    {
        return $this->restrict_type === 'brand' 
            ? $this->belongsTo(Brand::class, 'restrict_id')
            : $this->belongsTo(Category::class, 'restrict_id');
    }
}
