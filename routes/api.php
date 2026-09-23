<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ProductCardsController;
use App\Http\Controllers\Api\HomeRailsController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ReviewStatsController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\BookingLinkController as BookingLinkApiController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Api\V3SubscriberController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Api\StoreAbandonedCartController;
use App\Http\Controllers\StoreProductsController;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Cart APIs
Route::post('/cart/promo', [CartController::class, 'promo']);
Route::get('/cart/count', [CartController::class, 'count']);
Route::get('/cart/mini', [CartController::class, 'mini']);
Route::post('/cart/add', [CartController::class, 'add']);
Route::post('/cart/remove', [CartController::class, 'remove']);
Route::post('/cart/update', [CartController::class, 'update']);
Route::post('/cart/clear', [CartController::class, 'clear']);
Route::post('/cart/gift', [CartController::class, 'gift']);
Route::middleware('web')->group(function () {
    Route::post('/store/abandoned-cart', [StoreAbandonedCartController::class, 'track']);
    Route::post('/store/abandoned-cart/identify', [StoreAbandonedCartController::class, 'identify']);
});

// Checkout (Stripe)
Route::post('/checkout/session', [CheckoutController::class, 'createSession']);

// Lightweight frontend JSON endpoints
Route::get('/products', [ProductController::class, 'index']);
Route::get('/store/products', [StoreProductsController::class, 'apiIndex'])->name('api.store.products.index');
Route::get('/store/products/{slug}', [StoreProductsController::class, 'apiShow'])->name('api.store.products.show');
Route::get('/product-cards', [ProductCardsController::class, 'index']);
Route::get('/home/rails', [HomeRailsController::class, 'index']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/product-types', [ProductTypeController::class, 'index']);
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/review-stats', [ReviewStatsController::class, 'index']);

// Booking availability for v3 offerings
Route::get('/booking/offering/{offering}', [BookingLinkApiController::class, 'availability']);
Route::get('/booking/product/{product}', [BookingLinkApiController::class, 'availabilityForProduct']);

// Lightweight reservation hold/release endpoints
Route::post('/reservations/hold', [ReservationController::class, 'hold'])->name('api.reservations.hold');
Route::post('/reservations/release', [ReservationController::class, 'release'])->name('api.reservations.release');

// Browser subscriber forms require the Laravel session and CSRF token.
Route::middleware(['web', 'throttle:6,10'])->group(function () {
    Route::post('/v3-subscribers', [V3SubscriberController::class, 'store'])->name('api.v3-subscribers.store');
});
Route::post('/v3-subscribers/track', [V3SubscriberController::class, 'track'])
    ->middleware('throttle:30,10')
    ->name('api.v3-subscribers.track');
