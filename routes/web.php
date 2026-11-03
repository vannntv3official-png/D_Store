<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\InventoryAdminController;
use App\Http\Controllers\Admin\LoyaltyAdminController;
use App\Http\Controllers\Admin\MarketingAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\RefundAdminController;
use App\Http\Controllers\Admin\SettingAdminController;
use App\Http\Controllers\Admin\StaffAdminController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\ContentController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\OrderTrackingController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ShopController;
use App\Http\Controllers\LocaleCurrencyController;
use App\Http\Controllers\Webhook\AbaPaywayWebhookController;
use App\Http\Controllers\Webhook\KhqrWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes (CSRF exempt via bootstrap/app.php)
|--------------------------------------------------------------------------
*/
Route::post('/webhook/payment/khqr', KhqrWebhookController::class)->name('webhook.khqr');
Route::post('/webhook/payment/aba_payway', AbaPaywayWebhookController::class)->name('webhook.aba_payway');

/*
|--------------------------------------------------------------------------
| Locale & Currency Switching
|--------------------------------------------------------------------------
*/
Route::post('/locale/{locale}', [LocaleCurrencyController::class, 'setLocale'])->name('locale.set');
Route::post('/currency/{currency}', [LocaleCurrencyController::class, 'setCurrency'])->name('currency.set');

/*
|--------------------------------------------------------------------------
| Public Customer Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{slug}/reviews', [ProductController::class, 'storeReview'])->name('products.reviews.store');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::put('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/orders/{orderNumber}/confirmed', [OrderTrackingController::class, 'confirmed'])->name('orders.confirmed');
Route::get('/guest/orders/{token}', [OrderTrackingController::class, 'trackGuest'])->name('orders.guest');

Route::get('/pages/{slug}', [ContentController::class, 'page'])->name('pages.show');
Route::get('/page/{slug}', [ContentController::class, 'page'])->name('page.show');
Route::get('/sitemap.xml', [ContentController::class, 'sitemap'])->name('sitemap');
Route::get('/contact', [ContentController::class, 'contact'])->name('contact');
Route::post('/contact', [ContentController::class, 'submitContact'])->name('contact.submit');
Route::post('/newsletter/subscribe', [ContentController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');

/*
|--------------------------------------------------------------------------
| Customer Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
        Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
        Route::delete('/addresses/{id}', [AccountController::class, 'deleteAddress'])->name('addresses.delete');

        Route::get('/orders', [OrderTrackingController::class, 'accountOrders'])->name('orders');
        Route::get('/orders/{orderNumber}', [OrderTrackingController::class, 'accountOrderDetail'])->name('orders.show');

        Route::get('/loyalty', [AccountController::class, 'loyalty'])->name('loyalty');
        Route::post('/loyalty/redeem/{reward}', [AccountController::class, 'redeemReward'])->name('loyalty.redeem');

        Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');
        Route::post('/wishlist/{productId}', [AccountController::class, 'toggleWishlist'])->name('wishlist.toggle.id');
    });

    Route::post('/wishlist/toggle/{product}', [AccountController::class, 'toggleWishlist'])->name('wishlist.toggle');
});

/*
|--------------------------------------------------------------------------
| Staff / Merchant Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store']);
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

    Route::middleware(['auth', 'staff'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Products & Catalog
        Route::get('/products', [ProductAdminController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductAdminController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductAdminController::class, 'store'])->name('products.store');
        Route::get('/products/{id}/edit', [ProductAdminController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}', [ProductAdminController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [ProductAdminController::class, 'destroy'])->name('products.destroy');
        Route::get('/products/export', [ProductAdminController::class, 'exportCsv'])->name('products.export');
        Route::get('/products/export/csv', [ProductAdminController::class, 'exportCsv'])->name('products.export.csv');

        // Categories
        Route::get('/categories', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryAdminController::class, 'store'])->name('categories.store');
        Route::put('/categories/{id}', [CategoryAdminController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [CategoryAdminController::class, 'destroy'])->name('categories.destroy');

        // Orders & Invoices
        Route::get('/orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::post('/orders/{id}/transition', [OrderAdminController::class, 'transition'])->name('orders.transition');
        Route::get('/orders/{id}/invoice', [OrderAdminController::class, 'invoice'])->name('orders.invoice');

        // Refunds & Inspections
        Route::get('/refunds', [RefundAdminController::class, 'index'])->name('refunds.index');
        Route::post('/refunds', [RefundAdminController::class, 'store'])->name('refunds.store');
        Route::post('/refunds/{id}/restock', [RefundAdminController::class, 'inspectAndRestock'])->name('refunds.restock');

        // Inventory Stock & Audit Movements
        Route::get('/inventory', [InventoryAdminController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/adjust', [InventoryAdminController::class, 'adjust'])->name('inventory.adjust');
        Route::get('/inventory/movements', [InventoryAdminController::class, 'movements'])->name('inventory.movements');

        // Marketing
        Route::get('/marketing', [MarketingAdminController::class, 'index'])->name('marketing.index');
        Route::post('/marketing/coupons', [MarketingAdminController::class, 'storeCoupon'])->name('marketing.coupons.store');
        Route::post('/marketing/coupons/{id}/toggle', [MarketingAdminController::class, 'toggleCoupon'])->name('marketing.coupons.toggle');
        Route::post('/marketing/banners', [MarketingAdminController::class, 'storeBanner'])->name('marketing.banners.store');
        Route::delete('/marketing/banners/{id}', [MarketingAdminController::class, 'deleteBanner'])->name('marketing.banners.destroy');

        // Loyalty Rules & Adjustments
        Route::get('/loyalty', [LoyaltyAdminController::class, 'index'])->name('loyalty.index');
        Route::post('/loyalty/adjust', [LoyaltyAdminController::class, 'adjust'])->name('loyalty.adjust');
        Route::post('/loyalty/rewards', [LoyaltyAdminController::class, 'storeReward'])->name('loyalty.rewards.store');

        // Staff & RBAC
        Route::get('/staff', [StaffAdminController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffAdminController::class, 'store'])->name('staff.store');
        Route::put('/staff/{id}/roles', [StaffAdminController::class, 'updateRoles'])->name('staff.roles.update');
        Route::post('/staff/{id}/toggle', [StaffAdminController::class, 'toggleActive'])->name('staff.toggle');

        // Settings
        Route::get('/settings', [SettingAdminController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingAdminController::class, 'update'])->name('settings.update');
        Route::put('/settings', [SettingAdminController::class, 'update'])->name('settings.update.put');
    });
});
