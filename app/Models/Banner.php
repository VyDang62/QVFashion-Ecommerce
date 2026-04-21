<?php
namespace App\Models;

use App\Enums\BannerPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
class Banner extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $fillable = ['title', 'subtitle', 'image_path', 'link_url', 'position', 'order', 'is_active'];

    protected $casts = [
        'position' => BannerPosition::class,
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeAtPosition(Builder $query, BannerPosition $position): void
    {
        $query->where('position', $position);
    }
}