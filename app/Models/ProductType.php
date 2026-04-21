<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
class ProductType extends Model
{
    protected $fillable = ['type_name'];

    public function categories():HasMany{ return $this->hasMany(Category::class);    }

    protected static function booted()
    {
        static::creating(function ($productType) {
            if (empty($productType->slug)) {
                $productType->slug = Str::slug($productType->type_name);
            }
        });

        static::updating(function ($productType) {
            $productType->slug = Str::slug($productType->type_name);
        });
    }
}
