<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class ProductType extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = ['type_name','slug'];

    public function categories():HasMany{ return $this->hasMany(Category::class);    }

    protected static function booted()
    {
        static::creating(function ($productType) {
            if (empty($productType->slug)) {
                $productType->slug = static::generateUniqueTypeSlug($productType->type_name);
            }
        });

        static::updating(function ($productType) {
            if ($productType->isDirty('type_name')) {
                $productType->slug = static::generateUniqueTypeSlug($productType->type_name, $productType->id);
            }
        });
    }

    private static function generateUniqueTypeSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $i = 1;

        while (static::where('slug', $slug)
                    ->when($excludeId, fn($q, $id) => $q->where('id', '!=', $id))
                    ->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }
        return $slug;
    }
}
