<?php

namespace App\Observers;

use App\Models\Batch;

class BatchObserver
{
    /**
     * Handle the Batch "created" event.
     */
    public function created(Batch $batch): void
    {
        //Cộng số lượng sản phẩm vào variant
        $batch->variant->increment('stock_quantity',$batch->remaining_quantity);
    }

    /**
     * Handle the Batch "updated" event.
     */
    public function updated(Batch $batch): void
    {
        if($batch->isDirty('remaining_quantity')){
            $oldQuantity = $batch->getOriginal('remaining_quantity');
            $newQuantity = $batch->remaining_quantity;
            $diff = $newQuantity - $oldQuantity;

            $batch->variant->increment('stock_quantity',$diff);
        }
    }

    /**
     * Handle the Batch "deleted" event.
     */
    public function deleted(Batch $batch): void
    {
        $batch->variant->decrement('stock_quantity',$batch->remaining_quantity);
    }

    /**
     * Handle the Batch "restored" event.
     */
    public function restored(Batch $batch): void
    {
        //
    }

    /**
     * Handle the Batch "force deleted" event.
     */
    public function forceDeleted(Batch $batch): void
    {
        //
    }
}
