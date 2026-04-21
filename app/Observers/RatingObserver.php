<?php

namespace App\Observers;

use App\Models\Rating;
use Illuminate\Support\Facades\DB;

class RatingObserver
{

    private function updateProductRatingAvg($productId)
    {
        $average = DB::table('ratings')
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->avg('score');

        DB::table('products')
            ->where('id', $productId)
            ->update([
                'rating_avg' => round($average ?? 0, 2),
                'updated_at' => now()
            ]);
    }
    /**
     * Handle the Rating "created" event.
     */
    public function created(Rating $rating): void
    {
        if ($rating->is_approved) {
            $this->updateProductRatingAvg($rating->product_id);
        }
    }

    /**
     * Handle the Rating "updated" event.
     */
    public function updated(Rating $rating): void
    {
        if ($rating->isDirty(['score', 'is_approved'])) {
            $this->updateProductRatingAvg($rating->product_id);
        }
    }

    /**
     * Handle the Rating "deleted" event.
     */
    public function deleted(Rating $rating): void
    {
        $this->updateProductRatingAvg($rating->product_id);
    }

    /**
     * Handle the Rating "restored" event.
     */
    public function restored(Rating $rating): void
    {
        //
    }

    /**
     * Handle the Rating "force deleted" event.
     */
    public function forceDeleted(Rating $rating): void
    {
        //
    }
}
