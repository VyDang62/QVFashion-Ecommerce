<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\GHNService;
use App\Services\InventoryService;
use App\Services\VnpayService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    protected $voucherService;
    protected $ghnService;
    protected $inventoryService;
    protected $vnpayService;
    public function __construct(VoucherService $voucherService, GHNService $ghnService, InventoryService $inventoryService, VnpayService $vnpayService)
    {
        $this->voucherService = $voucherService;
        $this->ghnService = $ghnService;
        $this->inventoryService = $inventoryService;
        $this->vnpayService = $vnpayService;
    }
    public function checkout()
    {
        return Inertia::render('customer/Checkout');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        //Lấy giỏ hàng
        $cartItems = $user->cartItems()->with(['product', 'variant.flashSaleItems' => function($query) {
            $query->whereHas('flashSale', fn($q) => $q->current())
                ->whereRaw('sold_quantity < sale_quantity');
        }])->get()->sortBy('product_variant_id');

        if ($cartItems->isEmpty()) {
            return back()->with(['error' => 'Giỏ hàng của bạn đang trống!']);
        }

        $request->validate([
            'shipping_recipient_name' => 'required|string|max:255',
            'shipping_phone_number'   => 'required|string|max:20',
            'shipping_province_id'    => 'required|integer',
            'shipping_district_id'    => 'required|integer',
            'shipping_ward_code'      => 'required|string',
            'shipping_address_detail' => 'required|string|max:500',
            'payment_method'          => 'required|in:cod,banking',
        ]);

        $shippingFee = 0;
        try {
            $ghnData = $this->ghnService->calculateShippingFee([
                'district_id'    => $request->shipping_district_id,
                'ward_code'      => $request->shipping_ward_code,
                'total_amount'   => $cartItems->sum(fn($i) => $i->variant->price * $i->quantity),
                'total_quantity' => $cartItems->sum('quantity')
            ]);
            $shippingFee = $ghnData['data']['total'] ?? throw new Exception("Không thể tính phí giao hàng.");
        } catch (Exception $e) {
            return back()->with(['error' => 'Lỗi vận chuyển: ' . $e->getMessage()]);
        }

        try {
            $order = DB::transaction(function () use ($request, $user, $cartItems, $shippingFee) {
                $totalCost = 0;
                $orderItemsData = [];

                foreach ($cartItems as $item) {
                    $originalPrice = $item->variant->price;
                    $appliedPrice = $originalPrice;
                    $flashSaleId = null;
                    $flashSaleItemModel = null;

                    $fsItem = $item->variant->flashSaleItems()->lockForUpdate()->first();

                    if ($fsItem && ($fsItem->sold_quantity + $item->quantity <= $fsItem->sale_quantity)) {
                        //Kiểm tra giới hạn mua
                        if ($fsItem->user_limit > 0) {
                            $alreadyBought = OrderDetail::where('product_variant_id', $item->product_variant_id)
                                ->where('flash_sale_id', $fsItem->flash_sale_id)
                                ->whereHas('order', fn($q) => $q->where('user_id', $user->id))
                                ->sum('quantity');

                            if (($alreadyBought + $item->quantity) > $fsItem->user_limit) {
                                throw new Exception("Sản phẩm {$item->product->product_name} vượt giới hạn mua Flash Sale!");
                            }
                        }
                        $appliedPrice = $fsItem->sale_price;
                        $flashSaleId = $fsItem->flash_sale_id;
                        $flashSaleItemModel = $fsItem;
                    }

                    $subTotal = $appliedPrice * $item->quantity;
                    $totalCost += $subTotal;

                    $orderItemsData[] = [
                        'item'           => $item,
                        'product_id'     => $item->product_id,
                        'category_id'    => $item->product->category_id,
                        'brand_id'       => $item->product->brand_id,
                        'unit_price'     => $appliedPrice,
                        'original_price' => $originalPrice,
                        'flash_sale_id'  => $flashSaleId,
                        'sub_total'      => $subTotal,
                        'fs_model'       => $flashSaleItemModel,
                        'quantity'       => $item->quantity
                    ];
                }

                //Tính Voucher
                $discountAmount = 0;
                $voucherId = null;
                if ($request->voucher_code) {
                    $voucherData = $this->voucherService->calculateDiscount($request->voucher_code, $user, $orderItemsData);
                    $discountAmount = $voucherData['discount_amount'];
                    $voucherId = $voucherData['voucher_id'];
                }

                //Lưu đơn hàng
                $order = Order::create([
                    'order_code'              => Str::uuid(),
                    'user_id'                 => $user->id,
                    'total_cost'              => $totalCost,
                    'voucher_id'              => $voucherId,
                    'discount_amount'         => $discountAmount,
                    'shipping_fee'            => $shippingFee,
                    'final_amount'            => max(0, ($totalCost - $discountAmount) + $shippingFee),
                    'order_status'            => ($request->payment_method === 'cod') ? 1 : 2,
                    'shipping_province'       => $request->shipping_province,
                    'shipping_province_id'    => $request->shipping_province_id,
                    'shipping_district'       => $request->shipping_district,
                    'shipping_district_id'    => $request->shipping_district_id,
                    'shipping_ward'           => $request->shipping_ward,
                    'shipping_ward_code'      => $request->shipping_ward_code,
                    'shipping_address_detail' => $request->shipping_address_detail,
                    'shipping_phone_number'   => $request->shipping_phone_number,
                    'shipping_recipient_name' => $request->shipping_recipient_name,
                    'payment_method'          => $request->payment_method,
                    'order_note'              => $request->order_note,
                ]);

                //Tạo chi tiết và Trừ kho
                foreach ($orderItemsData as $data) {
                    OrderDetail::create([
                        'order_id'           => $order->id,
                        'product_variant_id' => $data['item']->product_variant_id,
                        'flash_sale_id'      => $data['flash_sale_id'],
                        'product_name'       => $data['item']->product->product_name,
                        'variant_info'       => $data['item']->variant->sku,
                        'original_price'     => $data['original_price'],
                        'unit_price'         => $data['unit_price'],
                        'quantity'           => $data['quantity'],
                        'sub_total'          => $data['sub_total'],
                    ]);

                    if ($data['fs_model']) {
                        $data['fs_model']->increment('sold_quantity', $data['quantity']);
                    }
                    //Tăng order_count
                    $data['item']->variant->product()->increment('order_count', $data['quantity']);

                    //Gọi inventory service
                    $this->inventoryService->deductStock($data['item']->product_variant_id, $data['quantity']);
                }

                //Cập nhật Voucher Usage
                if ($voucherId) {
                    Voucher::where('id', $voucherId)->increment('used_count');
                    VoucherUsage::create([
                        'voucher_id'      => $voucherId,
                        'user_id'         => $user->id,
                        'order_id'        => $order->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }

                $user->cartItems()->delete();
                return $order;
            });

            if ($request->payment_method === 'banking') {
                return Inertia::location($this->vnpayService->createPaymentUrl($order));
            }

            Mail::to($user->email)->queue(new OrderPlacedMail($order));

            return redirect()->route('orderhistory')->with('success', 'Đơn hàng đã được đặt thành công!');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đặt hàng: ' . $e->getMessage())->withInput();
        }
    }

    public function vnpayReturn(Request $request)
    {
        if(!$this->vnpayService->verifyHash($request->all())){
            return redirect()->route('orderhistory')->with('error', 'Chữ ký không hợp lệ!');
        }

        $order = Order::findOrFail($request->vnp_TxnRef); //vnp_TxnRef là order id

        if ($request->vnp_ResponseCode == '00') {
            DB::transaction(function () use ($order, $request) {
                if ($order->order_status == 2) {
                    $order->update(['order_status' => 3]); //Đã thanh toán
                    
                    //Lưu thông tin payment
                    Payment::create([
                        'order_id'              => $order->id,
                        'transaction_no'        => $request->vnp_TransactionNo,
                        'transaction_reference' => $request->vnp_TxnRef,
                        'amount'                => $request->vnp_Amount / 100,
                        'pay_date'              => $request->vnp_PayDate,
                        'bank_code'             => $request->vnp_BankCode,
                        'card_type'             => $request->vnp_CardType,
                        'response_code'         => $request->vnp_ResponseCode,
                        'order_info'            => $request->vnp_OrderInfo,
                    ]);

                    Mail::to($order->user->email)->send(new OrderPlacedMail($order));
                }
            });
            return redirect()->route('orderhistory')->with('success', 'Thanh toán thành công!');
        } else {
            //Thanh toán thất bại hoặc khách ấn hủy (Mã 24)
            if ($order->order_status != 0) {
                DB::transaction(function () use ($order) {
                    $order->update(['order_status' => 0]); //Đổi sang 0: Hủy
                    $this->inventoryService->restoreStock($order); //Hoàn kho
                    
                    //Hoàn lại lượt dùng Voucher (nếu có)
                    if ($order->voucher_id) {
                        Voucher::where('id', $order->voucher_id)->decrement('used_count');
                        VoucherUsage::where('order_id', $order->id)->delete();
                    }

                    //Nếu đơn có flash sale, hoàn lại sold_quantity
                    foreach ($order->details as $detail) {
                        if ($detail->flash_sale_id) {
                            DB::table('flash_sale_items')
                                ->where('flash_sale_id', $detail->flash_sale_id)
                                ->where('product_variant_id', $detail->product_variant_id)
                                ->decrement('sold_quantity', $detail->quantity);
                        }
                    }
                });
            }
            $message = $this->getVnpayErrorMessage($request->vnp_ResponseCode);
            return redirect()->route('orderhistory')->with('error', 'Thanh toán thất bại: ' . $message);
        }
    }

    private function getVnpayErrorMessage($code)
    {
        $errors = [
            '24' => 'Khách hàng hủy giao dịch.',
            '09' => 'Giao dịch không thành công: Thẻ/Tài khoản chưa đăng ký Internet Banking.',
            '10' => 'Giao dịch không thành công: Xác thực thông tin thẻ/tài khoản không đúng quá 3 lần.',
            '11' => 'Giao dịch không thành công: Hết hạn chờ thanh toán.',
            '12' => 'Giao dịch không thành công: Thẻ/Tài khoản bị khóa.',
            '51' => 'Giao dịch không thành công: Tài khoản không đủ số dư.',
            'default' => 'Lỗi không xác định trong quá trình thanh toán.'
        ];

        return $errors[$code] ?? $errors['default'];
    }
}
