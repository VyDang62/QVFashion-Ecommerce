<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
class Category extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = ['category_name', 'category_slug', 'parent_id', 'gender', 'product_type_id'];
    protected $casts = [
        'gender' => Gender::class,
    ];
    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function scopeOnlyParents($query){
        return $query->whereNull('parent_id');
    }

    public function scopeGetOnlyChildren(Builder $query): void
    {
        $query->whereNotNull('parent_id');
    }
    public function productType() {
        return $this->belongsTo(ProductType::class,'product_type_id');
    }
    
    public function products() { return $this->hasMany(Product::class); }

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->category_slug)) {
                $category->category_slug = static::generateUniqueCategorySlug($category->category_name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('category_name')) {
                $category->category_slug = static::generateUniqueCategorySlug($category->category_name, $category->id);
            }
        });
    }
    private static function generateUniqueCategorySlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $i = 1;

        while (static::where('category_slug', $slug)
                    ->when($excludeId, function($query, $id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }

        return $slug;
    }
}
