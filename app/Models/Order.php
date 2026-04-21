<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Order extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $appends = ['payment_method_label', 'status_info'];
    protected $fillable = [
        'order_code', 'order_date', 'total_cost', 'user_id', 
        'order_status', 'shipping_address_detail', 'shipping_ward', 'shipping_ward_code', 
        'shipping_district', 'shipping_district_id', 'shipping_province','shipping_province_id', 'shipping_phone_number',
        'shipping_recipient_name', 'shipping_fee', 'order_note', 'payment_method',
        'voucher_id', 'discount_amount', 'final_amount'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function voucherUsage()
    {
        return $this->hasOne(VoucherUsage::class);
    }

    public function payment()
    {
        $this->hasOne(Payment::class);
    }

    const STATUS_CANCELLED = 0;
    const STATUS_PENDING_COD = 1;
    const STATUS_PENDING_PAYMENT = 2;
    const STATUS_PAID = 3;
    const STATUS_SHIPPING = 4;
    const STATUS_SHIPPED = 5;
    const STATUS_COMPLETED = 6;

    const STATUS_RETURN_REQUESTED = 7; //Khách hàng bấm yêu cầu trả hàng
    const STATUS_RETURNING        = 8; //Shipper đang đi lấy hàng hoặc hàng đang trên đường về kho
    const STATUS_RETURNED         = 9; //Kho đã nhận được hàng và kiểm tra xong
    const STATUS_REFUNDED         = 10; //Đã hoàn tiền thành công qua VNPAY
    public static function getStatusList()
    {
        return [
            self::STATUS_CANCELLED      => ['label' => 'Đã hủy', 'class' => 'text-red-600', 'bg' => 'bg-red-100', 'badge_admin' => 'error'],
            self::STATUS_PENDING_COD    => ['label' => 'Chờ xử lý (COD)', 'class' => 'text-orange-600', 'bg' => 'bg-orange-100', 'badge_admin' => 'warning'],
            self::STATUS_PENDING_PAYMENT => ['label' => 'Chờ thanh toán', 'class' => 'text-blue-600', 'bg' => 'bg-blue-100', 'badge_admin' => 'info'],
            self::STATUS_PAID           => ['label' => 'Đã thanh toán', 'class' => 'text-teal-600', 'bg' => ' bg-teal-100', 'badge_admin' => 'teal'],
            self::STATUS_SHIPPING       => ['label' => 'Đang giao hàng', 'class' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'badge_admin' => 'primary'],
            self::STATUS_SHIPPED        => ['label' => 'Đã giao hàng', 'class' => 'text-purple-600', 'bg' => 'bg-purple-100', 'badge_admin' => 'secondary'],
            self::STATUS_COMPLETED      => ['label' => 'Hoàn thành', 'class' => 'text-green-600 ', 'bg' => 'bg-green-100', 'badge_admin' => 'success'],
            self::STATUS_RETURN_REQUESTED => ['label' => 'Yêu cầu trả hàng', 'class' => 'text-rose-600', 'bg' => 'bg-rose-100', 'badge_admin' => 'danger' ],
            self::STATUS_RETURNING => ['label' => 'Đang thu hồi hàng', 'class' => 'text-pink-600', 'bg' => 'bg-pink-100', 'badge_admin' => 'pink' ],
            self::STATUS_RETURNED => ['label' => 'Đã nhận hàng trả', 'class' => 'text-slate-600', 'bg' => 'bg-slate-100', 'badge_admin' => 'slate' ],
            self::STATUS_REFUNDED => ['label' => 'Đã hoàn tiền','class' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'badge_admin' => 'teal' ],
        ];
    }
    /**
     * Accessor: Gọi $order->status_info trong code
     */
    public function getStatusInfoAttribute()
    {
        return self::getStatusList()[$this->order_status] ?? ['label' => 'Không xác định', 'class' => '', 'bg' => ''];
    }
    public function getPaymentMethodLabelAttribute()
    {
        return [
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'banking' => 'Thanh toán qua VNPay',
        ][$this->payment_method] ?? 'Không xác định';
    }
}
