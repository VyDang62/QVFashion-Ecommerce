<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\Auth\NewPasswordController as AdminNewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController as AdminPasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\PasswordController as AdminPasswordController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductStatisticsController;
use App\Http\Controllers\Admin\RatingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\GoodsReceiptController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\GHNController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\OrderHistoryController;
use App\Http\Controllers\Customer\ShopController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\InventoryStatisticsController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\GHNService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
//API
Route::get('/api/locations/provinces', [LocationController::class, 'getProvinces']);
Route::get('/api/locations/provinces/{code}/districts', [LocationController::class, 'getDistricts']);
Route::get('/api/locations/districts/{code}/wards', [LocationController::class, 'getWards']);

Route::get('/api/ghn/provinces', [GHNController::class, 'provinces']);
Route::get('/api/ghn/districts/{code}', [GHNController::class, 'districts']);
Route::get('/api/ghn/wards/{code}', [GHNController::class, 'wards']);

Route::post('/api/ghn/calculateshippingfee', [GHNController::class, 'calculateShippingFee']);

Route::get('/checkout/vnpay-return', [CheckoutController::class, 'vnpayReturn'])->name('vnpay.return');
Route::get('/checkout/vnpay-ipn', [CheckoutController::class, 'vnpayIpn'])->name('vnpay.ipn');
//Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function (){
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store']);
        
        Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('forgot-password', [AdminPasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [AdminPasswordResetLinkController::class, 'store'])->name('password.email');
        
        Route::get('reset-password/{token}', [AdminNewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [AdminNewPasswordController::class, 'store'])->name('password.store');
    });

    Route::middleware(['auth','role:super-admin|warehouse-manager|sales-staff'])->group(function (){
        Route::get('/home', function () {
            return Inertia::render('admin/Dashboard');
        })->name('home');

        Route::post('logout',[AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/dashboard',[DashboardController::class, 'index'])->name('dashboard.overall');
        Route::get('/dashboard/productstatistics',[ProductStatisticsController::class, 'index'])->name('dashboard.productstatistics');
        Route::get('/dashboard/inventorystatistics',[InventoryStatisticsController::class, 'index'])->name('dashboard.inventorystatistics');
        Route::get('/dashboard/inventorystatistics/exportexcel',[InventoryStatisticsController::class, 'exportExcel'])->name('dashboard.inventorystatistics.exportExcel');
        Route::get('/dashboard/inventorystatistics/exportpdf',[InventoryStatisticsController::class, 'exportPdf'])->name('dashboard.inventorystatistics.exportPdf');
        
        Route::get('/dashboard/financialreport',[FinancialReportController::class, 'index'])->name('dashboard.financialreport');
        Route::get('dashboard/financialreport/export', [FinancialReportController::class, 'export'])->name('dashboard.financialreport.export');
        Route::get('dashboard/financialreport/exportpdf', [FinancialReportController::class, 'exportPdf'])->name('dashboard.financialreport.exportpdf');
        
        Route::resource('activitylogs', ActivityLogController::class);

        Route::resource('users', UserController::class);

        Route::resource('products', ProductController::class);
        Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
        Route::delete('products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.forcedelete')->withTrashed();;

        Route::resource('categories', CategoryController::class);
        Route::post('categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
        Route::delete('categories/{id}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.forcedelete')->withTrashed();;
        
        Route::resource('brands', BrandController::class);
        Route::post('brands/{id}/restore', [BrandController::class, 'restore'])->name('brands.restore');
        Route::delete('brands/{id}/force-delete', [BrandController::class, 'forceDelete'])->name('brands.forcedelete')->withTrashed();;

        Route::resource('attributes', AttributeController::class);
        Route::resource('producttypes', ProductTypeController::class);
        Route::post('producttypes/{id}/restore', [ProductTypeController::class, 'restore'])->name('producttypes.restore');
        Route::delete('producttypes/{id}/force-delete', [ProductTypeController::class, 'forceDelete'])->name('producttypes.forcedelete')->withTrashed();;

        Route::resource('roles', RoleController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::post('suppliers/{id}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
        Route::delete('suppliers/{id}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.forcedelete')->withTrashed();;

        Route::resource('goodsreceipts', GoodsReceiptController::class);
        Route::post('goodsreceipts/{goodsreceipt}/approve', [GoodsReceiptController::class, 'approve'])->name('goodsreceipts.approve');
        Route::post('goodsreceipts/{goodsreceipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('goodsreceipts.cancel');

        Route::get('inventory/batches',[BatchController::class, 'index'])->name('inventory.batches');
        Route::get('inventory/batches/{id}/edit',[BatchController::class, 'edit'])->name('inventory.batches.edit');
        Route::patch('inventory/batches/{id}/adjust',[BatchController::class, 'adjust'])->name('inventory.batches.adjust');

        Route::patch('users/{user}/update-info', [UserController::class, 'updateInfo'])->name('users.updateinfo');
        Route::patch('users/{user}/update-password', [UserController::class, 'updatePassword'])->name('users.updatepassword');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.togglestatus');
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('users/{id}/force-delete', [UserController::class, 'forceDelete'])->name('users.forcedelete')->withTrashed();;

        Route::get('/settings',[SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings',[SettingController::class, 'update'])->name('settings.update');

        Route::resource('banners', BannerController::class);
        Route::post('banners/{id}/restore', [BannerController::class, 'restore'])->name('banners.restore');
        Route::delete('banners/{id}/force-delete', [BannerController::class, 'forceDelete'])->name('banners.forcedelete')->withTrashed();;

        Route::resource('vouchers', VoucherController::class);
        Route::post('vouchers/{id}/restore', [VoucherController::class, 'restore'])->name('vouchers.restore');
        Route::delete('vouchers/{id}/force-delete', [VoucherController::class, 'forceDelete'])->name('vouchers.forcedelete')->withTrashed();;

        Route::resource('flashsales', FlashSaleController::class);
        Route::post('flashsales/{id}/restore', [FlashSaleController::class, 'restore'])->name('flashsales.restore');
        Route::delete('flashsales/{id}/force-delete', [FlashSaleController::class, 'forceDelete'])->name('flashsales.forcedelete')->withTrashed();;

        Route::get('/orders',[OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}',[OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{id}/edit',[OrderController::class, 'edit'])->name('orders.edit');
        Route::patch('/orders/{order}/update',[OrderController::class, 'update'])->name('orders.update');
        Route::get('/orders/{id}/exportexcel',[OrderController::class, 'exportExcel'])->name('orders.show.exportexcel');
        Route::get('/orders/{id}/exportpdf',[OrderController::class, 'exportPdf'])->name('orders.show.exportpdf');

        Route::get('/userprofile',[UserProfileController::class, 'index'])->name('userprofile.index');
        Route::patch('/userprofile/updateinfo',[UserProfileController::class, 'updateInfo'])->name('userprofile.updateinfo');
        Route::patch('/userprofile/updateaddress', [UserProfileController::class, 'updateAddress'])->name('userprofile.updateaddress');
        Route::put('/userprofile/updatepassword', [AdminPasswordController::class, 'update'])->name('userprofile.updatepassword');

        Route::resource('ratings', RatingController::class);
        Route::patch('/ratings/{id}/toggle-approval', [RatingController::class, 'toggleApproval'])->name('ratings.toggleapproval');
        
        Route::resource('pages', \App\Http\Controllers\Admin\PageController::class)->except(['show']);
        Route::post('pages/{id}/restore', [\App\Http\Controllers\Admin\PageController::class, 'restore'])->name('pages.restore');
        Route::delete('pages/{id}/force-delete', [\App\Http\Controllers\Admin\PageController::class, 'forceDelete'])->name('pages.force-delete');

        Route::get('/notifications',[NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::patch('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    });
});

//Customer
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/shop/{slug?}', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');

Route::get('/product/{slug?}',[CustomerProductController::class,'show'])->name('product.show');

Route::get('/cart',[CartController::class,'index'])->name('cart.index');
Route::post('/cart/add',[CartController::class,'store'])->name('cart.add');
Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}/destroy',[CartController::class,'destroy'])->name('cart.destroy');
Route::delete('/cart/clear',[CartController::class,'clear'])->name('cart.clear');

Route::middleware(['auth'])->group(function () {
    Route::post('/cart/applyvoucher',[CartController::class,'applyVoucher'])->name('cart.applyvoucher');
    Route::get('/checkout',[CheckoutController::class,'checkout'])->name('checkout');
    Route::post('/createorder',[CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/profile/orderhistory',[OrderHistoryController::class,'index'])->name('orderhistory');
    Route::get('/profile/orderhistory/order/{order_code}',[OrderHistoryController::class,'show'])->name('orderhistory.show');
    Route::patch('/profile/orderhistory/orderupdate/{order_code}',[OrderHistoryController::class,'update'])->name('orderhistory.update');

    Route::post('/product/{id}/review',[CustomerProductController::class, 'store'])->name('product.review.store');

    Route::post('/profile/wishlist/add',[WishlistController::class,'store'])->name('wishlist.store');
    Route::get('/profile/wishlist',[WishlistController::class,'index'])->name('wishlist');
});

Route::get('/page/{slug}', [\App\Http\Controllers\Customer\PageController::class, 'show'])->name('pages.show');
//Debug
Route::get('/test-offline-notification', function () {
    // 1. Lấy ID variant
    $variantId = request('id');
    $variant = ProductVariant::with('product')->find($variantId);

    if (!$variant) return "Không thấy Variant!";

    // 2. Lấy TẤT CẢ Admin (hoặc Warehouse Manager) 
    // Kể cả những người đang không đăng nhập
    $admins = User::role(['super-admin', 'warehouse-manager'])->get();

    if ($admins->isEmpty()) return "Không tìm thấy Admin nào trong DB để gửi!";

    // 3. Gửi cho cả dàn Admin
    Notification::send($admins, new LowStockNotification($variant));

    return "Hệ thống đã bí mật gửi thông báo cho " . $admins->count() . " Admin. 
            Bây giờ hãy thử dùng tài khoản Admin khác để đăng nhập và xem cái chuông nhé!";
});


require __DIR__.'/auth.php';
