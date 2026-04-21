<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "created" event.
     */
    public function created(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "updated" event.
     */
    public function updated(ProductVariant $productVariant): void
    {
        if($productVariant->wasChanged('stock_quantity')){
            if($productVariant->stock_quantity <= $productVariant->low_stock_threshold){
                //Chỉ gửi thông báo 1 lần mỗi 24 giờ cho cùng 1 biến thể
                $cacheKey = "low_stock_notified_{$productVariant->id}";
                if(!Cache::has($cacheKey)){
                    $admins = User::role(['super-admin','warehouse-manager'])->with('notificationSettings')->get();

                    if($admins->isNotEmpty()){
                        Notification::send($admins,new LowStockNotification($productVariant));
                        Cache::put($cacheKey,true,1440);
                    }
                }
            }
        }
    }

    /**
     * Handle the ProductVariant "deleted" event.
     */
    public function deleted(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "restored" event.
     */
    public function restored(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "force deleted" event.
     */
    public function forceDeleted(ProductVariant $productVariant): void
    {
        //
    }
}
