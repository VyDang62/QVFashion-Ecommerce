<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'transaction_no',
        'transaction_reference',
        'amount',
        'pay_date',
        'bank_code',
        'card_type',
        'response_code',
        'order_info',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
