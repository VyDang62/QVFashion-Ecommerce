<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Brand extends Model
{
    protected $fillable = ['brand_name'];
    public function products() {return $this->hasMany(Product::class); }

    protected static function booted()
    {
        static::creating(function ($brand) {
            if (empty($brand->brand_slug)) {
                $brand->brand_slug = Str::slug($brand->brand_name);
            }
        });

        static::updating(function ($brand) {
            $brand->brand_slug = Str::slug($brand->brand_name);
        });
    }
}
