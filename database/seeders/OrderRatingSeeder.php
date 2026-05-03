<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comments = [
            'Sản phẩm đẹp, vải mặc rất mát, ưng ý lắm shop!',
            'Giao hàng nhanh, đóng gói cẩn thận, sẽ ủng hộ tiếp.',
            'Form áo rất chuẩn, mình 60kg mặc size M vừa in.',
            'Chất lượng tuyệt vời so với giá tiền.',
            'Màu sắc hơi đậm hơn trong ảnh một chút nhưng vẫn đẹp.',
            'Nhân viên tư vấn nhiệt tình, chọn đúng size cho mình luôn.',
            'Vải hơi mỏng so với tưởng tượng nhưng mặc mùa hè thì hợp.',
            'Đã mua lần 2, vẫn rất hài lòng với QVFashion.',
        ];
        $users = User::role('customer')->get();
        $variants = ProductVariant::all();

        if($users->isEmpty() || $variants->isEmpty()) return;

        for($i = 0; $i < 50; $i++){
            $user = $users->random();

            $order =Order::create([
                'order_code' => fake()->uuid(),
                'user_id' => $user->id,
                'total_cost' => 0,
                'order_status' => 6,
                'shipping_address_detail' => fake()->streetAddress(),
                'shipping_ward' => 'Phường Bến Nghé',
                'shipping_ward_code' => '20101',
                'shipping_district' => 'Quận 1',
                'shipping_district_id' => 1442,
                'shipping_province' => 'TP. Hồ Chí Minh',
                'shipping_province_id' => 202,
                'shipping_phone_number' => $user->phone_number,
                'shipping_recipient_name' => $user->full_name,
                'payment_method' => 'cod',
                'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
            ]);

            $totalOrderCost = 0;
            $orderVariants = $variants->random(rand(1, 3));

            foreach ($orderVariants as $variant) {
                $qty = rand(1, 2);
                $subTotal = $variant->price * $qty;
                
                $order->details()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $qty,
                    'unit_price' => $variant->price,
                    'sub_total' => $subTotal,
                ]);

                $totalOrderCost += $subTotal;

                if (rand(1, 10) <= 7) {
                    Rating::create([
                        'user_id' => $user->id,
                        'product_id' => $variant->product_id,
                        'score' => rand(4, 5),
                        'content' => fake()->randomElement($comments),
                        'is_approved' => true,
                        'created_at' => $order->created_at->addDays(rand(2, 5)),
                    ]);
                }
            }

            $order->update(['total_cost' => $totalOrderCost, 'final_amount' => $totalOrderCost]);
        }
    }
}
