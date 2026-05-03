<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Brand extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = ['brand_name','brand_slug'];
    public function products() {return $this->hasMany(Product::class); }

    protected static function booted()
    {
        static::creating(function ($brand) {
            if (empty($brand->brand_slug)) {
                $brand->brand_slug = static::generateUniqueBrandSlug($brand->brand_name);
            }
        });

        static::updating(function ($brand) {
            if ($brand->isDirty('brand_name')) {
                $brand->brand_slug = static::generateUniqueBrandSlug($brand->brand_name, $brand->id);
            }
        });
    }

    private static function generateUniqueBrandSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $i = 1;

        while (static::where('brand_slug', $slug)
                    ->when($excludeId, fn($q, $id) => $q->where('id', '!=', $id))
                    ->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }
        return $slug;
    }
}
