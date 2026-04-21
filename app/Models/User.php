<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
class User extends Authenticatable
{   
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name', 'email', 'password', 'phone_number', 
        'address_detail', 'ward','ward_code', 'district','district_id', 'province','province_id', 'is_active',
    ];
    protected $dates = ['deleted_at'];
    public function orders(): HasMany {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function ratings(): HasMany {
        return $this->hasMany(Rating::class, 'user_id');
    }

    public function goodsReceipts(): HasMany {
        return $this->hasMany(GoodsReceipt::class, 'user_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'user_id')->latest();
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists', 'user_id', 'product_id')
                    ->withTimestamps();
    }

    public function voucherUsages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at','desc');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    public function notificationSettings()
    {
        return $this->hasMany(NotificationSetting::class);
    }

    public function getEnabledChannels(string $type): array
    {
        return $this->notificationSettings()
            ->where('notification_type', $type)
            ->where('is_enabled', true)
            ->pluck('channel') // Lấy mảng các giá trị như ['database', 'broadcast']
            ->toArray();
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
