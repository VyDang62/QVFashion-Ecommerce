<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\Gender;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $jsonPath = database_path('data/fwrd_final_data.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("Không tìm thấy file JSON!");
            return;
        }
        $jsonContent = File::get($jsonPath);

        // 2. Kiểm tra file có bị trống không
        if (empty($jsonContent)) {
            $this->command->error("❌ LỖI: File JSON hoàn toàn trống rỗng!");
            return;
        }
        $productsData = json_decode(File::get($jsonPath), true);

        if (is_null($productsData)) {
            $error = json_last_error_msg();
            $this->command->error("Lỗi giải mã JSON: $error");
            // Nếu Vy muốn soi kỹ hơn nội dung file khi lỗi:
            dd($jsonContent); 
            return;
        }
        // 1. Tạo nhà cung cấp mặc định
        $supplier = Supplier::firstOrCreate(
            ['supplier_name' => 'FWRD Global Supplier'],
            ['phone' => '0123456789', 'supplier_address' => 'Global']
        );

        // 2. Lấy user admin
        $admin = User::whereHas('roles', function($q){
            $q->where('name', 'super-admin');
        })->first() ?? User::first();

        if (!$admin) {
            $this->command->error("Cần có ít nhất 1 User để tạo phiếu nhập kho!");
            return;
        }

        // 3. Tạo một phiếu nhập kho ban đầu
        $receipt = GoodsReceipt::create([
            'receipt_code' => 'GR-' . strtoupper(Str::random(8)),
            'supplier_id'  => $supplier->id,
            'user_id'      => $admin->id,
            'total_cost'   => 0,
            'receipt_status' => 'completed',
            'receipt_date' => now(),
        ]);

        $totalReceiptCost = 0;

        foreach ($productsData as $item) {
            if (empty($item['variants'])) continue;

            DB::transaction(function () use ($item, $receipt, &$totalReceiptCost) {
                // --- 1. Xử lý Brand ---
                $brand = Brand::firstOrCreate(
                    ['brand_name' => $item['brand_name']],
                    ['brand_slug' => Str::slug($item['brand_name'])]
                );

                $genderMap = [
                    1 => Gender::FEMALE->value,
                    2 => Gender::MALE->value,
                ];
                $genderValue = $genderMap[$item['gender_id']] ?? Gender::FEMALE->value;

                // --- 2. Xử lý Category Hierarchy với logic chống trùng Slug ---
                
                // --- CATEGORY CHA ---
                $parentData = [
                    'category_name'   => $item['parent_category_text'],
                    'parent_id'       => null,
                    'gender'          => $genderValue,
                    'product_type_id' => $item['product_type_id']
                ];

                // Kiểm tra xem danh mục này (với các thuộc tính này) đã tồn tại chưa
                $parentCat = Category::where($parentData)->first();

                if (!$parentCat) {
                    // Nếu chưa có, tạo slug duy nhất rồi mới create
                    $parentData['category_slug'] = $this->generateUniqueSlug($item['parent_category_text']);
                    $parentCat = Category::create($parentData);
                }

                // --- CATEGORY CON ---
                $childData = [
                    'category_name'   => $item['child_category_text'],
                    'parent_id'       => $parentCat->id,
                    'gender'          => $genderValue,
                    'product_type_id' => $item['product_type_id']
                ];

                $childCat = Category::where($childData)->first();

                if (!$childCat) {
                    // Tương tự, tạo slug duy nhất cho category con
                    $childData['category_slug'] = $this->generateUniqueSlug($item['child_category_text']);
                    $childCat = Category::create($childData);
                }

                // --- 3. Tạo Sản phẩm chính ---
                $product = Product::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'product_name' => $item['product_name'],
                        'product_description' => $item['product_description'],
                        'brand_id' => $brand->id,
                        'category_id' => $childCat->id,
                        'is_active' => $item['is_active'] ?? true,
                    ]
                );

                // 4. Hình ảnh
                foreach ($item['images'] as $img) {
                    ProductImage::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'image_path' => $img['image_path']
                        ],
                        ['is_primary' => $img['is_primary'] ?? false]
                    );
                }

                // 5. Biến thể và LÔ HÀNG
                foreach ($item['variants'] as $vData) {
                    $qty = $vData['stock_quantity'] ?? 100;
                    $purchasePrice = round($vData['price'] * 0.7);

                    $variant = ProductVariant::updateOrCreate(
                        ['sku' => $vData['variant_sku']],
                        [
                            'product_id' => $product->id,
                            'price' => $vData['price'],
                            'stock_quantity' => 0, 
                        ]
                    );

                    Batch::create([
                        'product_variant_id' => $variant->id,
                        'goods_receipt_id'   => $receipt->id,
                        'batch_code'         => 'BATCH-' . $variant->sku . '-' . strtoupper(Str::random(4)),
                        'purchase_price'     => $purchasePrice,
                        'original_quantity'  => $qty,
                        'remaining_quantity' => $qty,
                        'received_date'      => now(),
                    ]);

                    $totalReceiptCost += ($purchasePrice * $qty);

                    foreach ($vData['variant_attributes'] as $vAttr) {
                        $attribute = Attribute::firstOrCreate(['attribute_name' => $vAttr['name']]);
                        $attrValue = AttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'value' => (string)$vAttr['value']
                        ]);

                        DB::table('product_variant_attribute_values')->updateOrInsert([
                            'variant_id' => $variant->id,
                            'attribute_value_id' => $attrValue->id
                        ]);
                    }
                }
            });
        }

        $receipt->update(['total_cost' => $totalReceiptCost]);
        $this->command->info("Nạp dữ liệu thành công! Đã xử lý trùng lặp slug danh mục.");
    }

    /**
     * Hàm hỗ trợ tạo Slug duy nhất
     */
    private function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $i = 1;

        // Vòng lặp kiểm tra trong DB, nếu tồn tại slug này rồi thì thêm số vào sau
        // Ví dụ: strapless -> strapless-1 -> strapless-2
        while (Category::where('category_slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}