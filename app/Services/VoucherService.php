<?php
namespace App\Services;

use App\Enums\VoucherType;
use App\Models\Voucher;
use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class VoucherService
{
    public function calculateDiscount(string $code, User $user, ?array $items = null)
    {
        $voucher = Voucher::with('restrictions')
            ->valid()
            ->where('code', $code)
            ->lockForUpdate() 
            ->first();

        if (!$voucher) {
            $this->throwError('Mã giảm giá không tồn tại hoặc đã hết hạn.');
        }

        //Kiểm tra tổng giới hạn lượt dùng toàn hệ thống
        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            $this->throwError('Mã giảm giá này đã hết lượt sử dụng.');
        }

        // Kiểm tra giới hạn lượt dùng của riêng User này
        $userUsageCount = VoucherUsage::where('voucher_id', $voucher->id)
            ->where('user_id', $user->id)
            ->count();

        if ($userUsageCount >= $voucher->per_user_limit) {
            $this->throwError('Bạn đã hết lượt sử dụng mã này.');
        }

        if (is_null($items)) {
            $items = $user->cartItems()->with(['product', 'variant.flashSaleItems' => function($q) {
                $q->whereHas('flashSale', fn($query) => $query->current());
            }])->get()->map(function($cartItem) {
                $fsItem = $cartItem->variant->flashSaleItems->first();
                return [
                    'product_id'  => $cartItem->product_id,
                    'category_id' => $cartItem->product->category_id,
                    'brand_id'    => $cartItem->product->brand_id,
                    'unit_price'  => $fsItem ? $fsItem->sale_price : $cartItem->variant->price,
                    'quantity'    => $cartItem->quantity,
                ];
            })->toArray();
        }

        if (empty($items)) {
            $this->throwError('Giỏ hàng trống, không thể áp dụng mã.');
        }

        $eligibleTotal = 0;
        $fullCartTotal = 0;
        $restrictions = $voucher->restrictions;

        foreach ($items as $item) {
            $itemTotal = $item['unit_price'] * $item['quantity'];
            $fullCartTotal += $itemTotal;

            if ($restrictions->isEmpty()) {
                //Không có giới hạn
                $eligibleTotal += $itemTotal;
            } else {
                foreach ($restrictions as $res) {
                    $isMatch = false;
                    if ($res->restrict_type === 'product' && $item['product_id'] == $res->restrict_id) {
                        $isMatch = true;
                    } elseif ($res->restrict_type === 'category' && $item['category_id'] == $res->restrict_id) {
                        $isMatch = true;
                    } elseif ($res->restrict_type === 'brand' && $item['brand_id'] == $res->restrict_id) {
                        $isMatch = true;
                    }

                    if ($isMatch) {
                        $eligibleTotal += $itemTotal;
                        break;
                    }
                }
            }
        }

        //Kiểm tra giá trị đơn hàng tối thiểu
        if ($eligibleTotal < $voucher->min_order_value) {
            $this->throwError('Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($voucher->min_order_value) . 'đ hoặc chưa được áp dụng cho các sản phẩm được chọn.');
        }

        //Tính toán con số giảm giá cuối cùng
        $discountAmount = 0;
        if ($voucher->voucher_type === VoucherType::PERCENTAGE) {
            $discountAmount = ($eligibleTotal * $voucher->discount_value) / 100;
            //Áp trần giảm giá nếu có max_discount_amount
            if ($voucher->max_discount_amount) {
                $discountAmount = min($discountAmount, $voucher->max_discount_amount);
            }
        } else {
            //Giảm tiền cố định
            $discountAmount = $voucher->discount_value;
        }

        //Không giảm quá số tiền khách phải trả
        $discountAmount = min($discountAmount, $eligibleTotal);

        return [
            'voucher_id'      => $voucher->id,
            'code'            => $voucher->code,
            'discount_amount' => round($discountAmount),
            'eligible_total'  => $eligibleTotal,
            'full_cart_total' => $fullCartTotal,
            'new_total'       => $fullCartTotal - $discountAmount
        ];
    }

    private function throwError($message)
    {
        throw ValidationException::withMessages(['voucher' => $message]);
    }
}