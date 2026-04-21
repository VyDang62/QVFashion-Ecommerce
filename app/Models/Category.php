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
            if (empty($product->category_slug)) {
                $slug = Str::slug($category->category_name);

                $originalSlug = $slug;
                $i = 1;
                while (static::where('category_slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $i++;
                }
                
                $category->category_slug = $slug;
            }
        });

        static::updating(function ($category) {
            $category->category_slug = Str::slug($category->category_name);
        });

        
    }
}
