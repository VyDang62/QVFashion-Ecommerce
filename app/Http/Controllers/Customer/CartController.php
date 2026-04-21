<?php

namespace App\Http\Controllers\Customer;

use App\Enums\VoucherType;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\VoucherService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index()
    {
        return Inertia::render('customer/Cart');
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ],[
            'product_variant_id.required' => 'Vui lòng chọn size/màu sắc!'
        ]);

        $variantId = $request->product_variant_id;
        $quantity = $request->quantity;

        $variant = ProductVariant::findOrFail($variantId);
        if($variant->stock_quantity < $quantity){
            return back()->withErrors(['quantity' => 'Số lượng size/màu sắc này chỉ còn '. $variant->stock_quantity . '!']);
        }

        if(Auth::check()){
            $this->handleAuthenticatedCart($request->product_id, $variantId, $quantity);
        }else{
            $this->handleSessionCart($request->product_id, $variantId, $quantity);
        }
        return back()->with('success', 'Thêm vào giỏ hàng thành công!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $newQuantity = $request->quantity;

        if(Auth::check()){
            $cartItem = CartItem::where('user_id',Auth::id())->findorFail($id);
            if($cartItem->variant->stock_quantity < $newQuantity){
                return back()->withErrors(['quantity' => 'Số lượng size/màu này không đủ!']);
            }

            $cartItem->update(['quantity' => $newQuantity]);
        }else {
            $cart = session()->get('cart', []);

            if (isset($cart[$id])) {
                $variant = ProductVariant::findOrFail($id);
                
                if ($variant->stock_quantity < $newQuantity) {
                    return back()->withErrors(['quantity' => 'Số lượng tồn kho đã đạt giới hạn.']);
                }

                $cart[$id]['quantity'] = $newQuantity;
                session()->put('cart', $cart);
            }
        }
        return back()->with('success', 'Đã cập nhật giỏ hàng!');
    }

    public function applyVoucher(Request $request, VoucherService $voucherService)
    {
        $request->validate(['code' => 'required|string']);

        try {
            $result = $voucherService->calculateDiscount($request->code, Auth::user());
            
            return response()->json([
                'success' => true,
                'data'    => $result,
                'message' => 'Áp dụng mã thành công!'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        if(Auth::check()){
            CartItem::where('user_id', Auth::id())
                ->where('id',$id)
                ->delete();
        }else {
            $cart = session()->get('cart', []);

            if(isset($cart[$id])){
                unset($cart[$id]);
                session()->put('cart',$cart);
            }
        }
        return back()->with('success', 'Xóa khỏi giỏ hàng thành công!');
    }

    public function clear()
    {
        if(Auth::check()){
            CartItem::where('user_id',Auth::id())->delete();
        }else {
            session()->forget('cart');
        }

        return back()->with('success','Đã xóa thành công giỏ hàng!');
    }

    private function handleAuthenticatedCart($productId, $variantId, $quantity)
    {

        $cartItem = CartItem::where('user_id', Auth::id())
                            ->where('product_variant_id', $variantId)
                            ->first();
        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            CartItem::create([
                'user_id'            => Auth::id(),
                'product_id'         => $productId,
                'product_variant_id' => $variantId,
                'quantity'           => $quantity,
            ]);
        }
    }

    private function handleSessionCart($productId, $variantId, $quantity)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$variantId])) {
            $cart[$variantId]['quantity'] += $quantity;
        } else {
            $cart[$variantId] = [
                'product_id'         => $productId,
                'product_variant_id' => $variantId,
                'quantity'           => $quantity,
            ];
        }
        session()->put('cart', $cart);
    }
}
