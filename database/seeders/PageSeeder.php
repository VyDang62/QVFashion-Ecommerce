<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Về chúng tôi',
                'content' => '
                    <h2 class="text-2xl font-bold mb-4">Chào mừng đến với QVFashion</h2>
                    <p class="mb-4">Được thành lập vào năm 2025, QVFashion không chỉ là một thương hiệu thời trang mà còn là biểu tượng của sự tự tin và phong cách sống hiện đại.</p>
                    <p class="mb-4">Chúng tôi tin rằng mỗi bộ trang phục bạn khoác lên người đều kể một câu chuyện riêng về cá tính và khát vọng của bạn. Với đội ngũ thiết kế tài năng và quy trình sản xuất nghiêm ngặt, QVFashion cam kết mang đến những sản phẩm chất lượng cao nhất.</p>
                    <h3 class="text-xl font-bold mt-6 mb-2">Giá trị cốt lõi của chúng tôi:</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Chất lượng:</strong> Luôn ưu tiên những chất liệu vải bền bỉ và thoải mái nhất.</li>
                        <li><strong>Sáng tạo:</strong> Không ngừng cập nhật những xu hướng thời trang quốc tế.</li>
                        <li><strong>Khách hàng:</strong> Trải nghiệm mua sắm của bạn là ưu tiên số 1 của chúng tôi.</li>
                    </ul>
                ',
                'meta_title' => 'Về QVFashion - Thương hiệu thời trang hàng đầu',
                'meta_description' => 'Tìm hiểu về câu chuyện, sứ mệnh và những giá trị mà QVFashion mang lại cho khách hàng Việt Nam.',
            ],
            [
                'title' => 'Chính sách đổi trả',
                'content' => '
                    <h2 class="text-2xl font-bold mb-4">Quy định đổi trả sản phẩm</h2>
                    <p class="mb-4">Tại QVFashion, chúng tôi hiểu rằng đôi khi bạn có thể thay đổi ý định hoặc chọn nhầm size. Chính sách đổi trả của chúng tôi cực kỳ linh hoạt:</p>
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                        <p class="font-bold mb-2">Điều kiện đổi trả:</p>
                        <ul class="list-decimal pl-6 space-y-2">
                            <li>Sản phẩm còn nguyên tem mác, chưa qua sử dụng hoặc giặt ủi.</li>
                            <li>Trong vòng <strong>7 ngày</strong> kể từ ngày nhận hàng.</li>
                            <li>Có hóa đơn mua hàng kèm theo.</li>
                        </ul>
                    </div>
                    <p class="mt-6 italic">* Lưu ý: Các sản phẩm nằm trong chương trình Flash Sale hoặc giảm giá trên 50% sẽ không hỗ trợ đổi trả.</p>
                ',
                'meta_title' => 'Chính sách đổi trả linh hoạt tại QVFashion',
                'meta_description' => 'Hướng dẫn chi tiết về cách thức và điều kiện đổi trả sản phẩm tại QVFashion trong vòng 7 ngày.',
            ],
            [
                'title' => 'Chính sách bảo mật',
                'content' => '
                    <h2 class="text-2xl font-bold mb-4">Cam kết bảo mật thông tin</h2>
                    <p class="mb-4">QVFashion coi trọng việc bảo vệ thông tin cá nhân của khách hàng. Chúng tôi cam kết sử dụng dữ liệu của bạn một cách minh bạch:</p>
                    <ul class="list-disc pl-6 space-y-4">
                        <li><strong>Mục đích thu thập:</strong> Chỉ thu thập thông tin cần thiết để xử lý đơn hàng và cung cấp các ưu đãi đặc quyền.</li>
                        <li><strong>Phạm vi sử dụng:</strong> Thông tin của bạn sẽ không bao giờ được bán hoặc chia sẻ cho bên thứ ba vì mục đích quảng cáo khi chưa có sự đồng ý.</li>
                        <li><strong>An toàn dữ liệu:</strong> Chúng tôi sử dụng các công nghệ bảo mật tiên tiến nhất để ngăn chặn việc truy cập trái phép vào tài khoản của bạn.</li>
                    </ul>
                ',
                'meta_title' => 'Chính sách bảo mật thông tin khách hàng - QVFashion',
                'meta_description' => 'Tìm hiểu cách QVFashion thu thập, bảo vệ và sử dụng thông tin cá nhân của bạn.',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create([
                'title'            => $pageData['title'],
                'slug'             => Str::slug($pageData['title']),
                'content'          => $pageData['content'],
                'is_active'        => true,
                'meta_title'       => $pageData['meta_title'],
                'meta_description' => $pageData['meta_description'],
                'meta_keywords'    => 'qvfashion, thoi trang, ' . strtolower($pageData['title']),
            ]);
        }
    }
}
