<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Supplier extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = ['supplier_name', 'phone', 'supplier_address'];
    public function goodsReceipts() { 
        return $this->hasMany(GoodsReceipt::class, 'supplier_id'); 
    }
}
