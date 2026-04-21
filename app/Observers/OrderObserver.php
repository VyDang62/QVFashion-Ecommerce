<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Notification;


class OrderObserver
{
    public function created(Order $order)
    {
        $order->load('user');

        $admins = User::role(['super-admin', 'sales'])->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewOrderNotification($order));
        }
    }
}
