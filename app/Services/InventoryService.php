<?php

namespace App\Services;
use App\Models\Batch;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function deductStock($variantId, $quantity)
    {
        return DB::transaction(function () use ($variantId, $quantity) {
            $batches = Batch::where('product_variant_id', $variantId)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('received_date', 'asc')
                ->orderBy('id','asc')
                ->lockForUpdate()
                ->get();
                
            $totalAvailable = $batches->sum('remaining_quantity');
            if ($totalAvailable < $quantity) {
                throw new \Exception("Số lượng yêu cầu ({$quantity}) vượt quá tồn kho hiện có ({$totalAvailable})!");
            }

            $remainingToDeduct = $quantity;
            foreach ($batches as $batch) {
                if ($remainingToDeduct <= 0) break;
                if (!$batch instanceof \Illuminate\Database\Eloquent\Model) {
                    throw new \Exception("Lỗi kiểu dữ liệu: " . get_class($batch));
                }
                $deductAmount = min($batch->remaining_quantity, $remainingToDeduct);
                
                $batch->update([
                    'remaining_quantity' => $batch->remaining_quantity - $deductAmount
                ]);

                $remainingToDeduct -= $deductAmount;
            }
            return true;
            
        });
    }

    public function restoreStock($order)
    {
        foreach ($order->details as $detail) {
            $quantityToRestore = $detail->quantity;

            $batches = Batch::where('product_variant_id', $detail->product_variant_id)
                ->orderBy('created_at', 'desc')
                ->get();

            $batches = Batch::hydrate($batches->toArray());

            foreach ($batches as $batch) {
                if ($quantityToRestore <= 0) break;

                $spaceInBatch = $batch->original_quantity - $batch->remaining_quantity;

                if ($spaceInBatch > 0) {
                    $restoreAmount = min($quantityToRestore, $spaceInBatch);

                    $batch->remaining_quantity += $restoreAmount;
                    $batch->save();

                    $quantityToRestore -= $restoreAmount;
                }
            }

            if ($quantityToRestore > 0) {
                $latestBatch = $batches->first();
                if ($latestBatch) {
                    $latestBatch->remaining_quantity += $quantityToRestore;
                    $latestBatch->save();
                }
            }
        }
    }
}