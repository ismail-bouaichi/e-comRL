<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ResetController;

use App\Http\Controllers\Api\ForgetController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\ProductCartController;
use App\Http\Controllers\Api\DeliveryLocationController;
use App\Http\Controllers\Api\DeliveryWorkerController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


//Login Routes


Route::post('/login',[AuthController::class,'login']);

Route::post('/login_delivery', [AuthController::class,'loginDelivery']);
Route::post('/register_delivery', [AuthController::class,'registerDelivery']);


Route::get('/user',[UserController::class,'user'])->middleware('auth:api');
Route::post('/register',[AuthController::class,'register']);
Route::post('/forgetPassword',[ForgetController::class,'forgetPassword']);
Route::post('/resetPassword',[ResetController::class,'resetPassword']);

Route::middleware('auth:api')->get('/delivery-worker/orders', [DeliveryController::class, 'getDeliveryWorkerOrders']);

Route::middleware('auth:api')->post('/delivery-worker/orders/{orderId}', [DeliveryController::class, 'acceptOrder']);

Route::middleware('auth:api')->post('/delivery-worker/orders/complete/{orderId}', [DeliveryController::class, 'completeOrder']);

//store routes

Route::get('/user/edit/{id}',[UserController::class,'edit']);
Route::post('/user/update/{id}',[UserController::class,'update']);



Route::get('/product/mostSelling',[ProductController::class,'bestSellingProduct']);

Route::middleware('throttle:60,1')->group(function () {


Route::get('/product/list',[ProductController::class,'index']);
Route::get('/categories',[ProductController::class,'category']);

Route::get('/brands',[ProductController::class,'brand']);


});

Route::get('/product/{id}',[ProductController::class,'show']);

Route::get('/search/{searchKey}',[ProductController::class,'search']);
Route::post('/user/validate-password', [UserController::class, 'validatePassword'])->middleware('auth:api');




//order Routes
Route::middleware('auth:api')->post('/order/create', [OrderController::class, 'store']);
Route::get('/success', [OrderController::class, 'success'])->name('checkout.success');


Route::get('/products', [ProductController::class, 'bestRatedProducts']);


Route::get('/cancel', [OrderController::class, 'cancel'])->name('checkout.cancel');
Route::post('/webhook', [OrderController::class, 'webhook']);


Route::get('/failed', [OrderController::class, 'failed'])->name('checkout.failed');



Route::middleware('auth:api')->get('/orders/{userId}', [OrderController::class, 'orderHistory']);
Route::get('/generateQrCode', [OrderController::class, 'generateQrCode']);



Route::post('/calculate-shipping', [OrderController::class, 'calculateShipping']);



//cart Route

Route::middleware('auth:api')->group(function() {
	Route::get('/cart/{id}', [CartController::class, 'index']);
	Route::get('/cart/show/{id}',[CartController::class,'show']);
	Route::post('/cart/update/{id}',[CartController::class,'update']);
	Route::post('/cart/add-to-cart',[CartController::class,'store']);
	Route::post('/cart/buy',[CartController::class,'create']);
	Route::delete('/cart/delete/{id}',[CartController::class,'destroy']);
});



Route::get('/user/favorites', [CommentController::class, 'getUserFavorites'])->middleware('auth:api');
Route::middleware('auth:api')->post('/product/{productId}/favorite', [CommentController::class, 'favoriteProduct']);

Route::get('/product/ratings/{product_id}', [CommentController::class, 'getProductRatings']);
Route::post('/product/ratings/{product_id}', [CommentController::class, 'ratingProduct'])->middleware('auth:api');

Route::post('/product/comment/{product_id}', [CommentController::class, 'comment'])->middleware('auth:api');
Route::get('/product/comments/{product_id}', [CommentController::class, 'getComments']);


// ========================================
// Real-Time Delivery Tracking Routes
// ========================================

// Delivery Worker Routes (require authentication)
Route::middleware('auth:api')->group(function () {
    // Get delivery worker's own profile
    Route::get('/delivery-worker/me', [DeliveryWorkerController::class, 'me']);
    
    // Update delivery worker status (available/on_delivery/offline)
    Route::put('/delivery-worker/{id}/status', [DeliveryWorkerController::class, 'updateStatus']);
    
    // Verify worker is assigned to an order (used by Node.js Socket.io)
    Route::get('/delivery-worker/verify-order/{orderId}', [DeliveryWorkerController::class, 'verifyOrderAssignment']);
    
    // Location tracking endpoints
    Route::post('/delivery-worker/location', [DeliveryLocationController::class, 'store']);
    Route::get('/delivery/{orderId}/worker-location', [DeliveryLocationController::class, 'getCurrentLocation']);
    Route::get('/delivery/{orderId}/location-history', [DeliveryLocationController::class, 'getLocationHistory']);
});

// Admin/Manager Routes
Route::middleware(['auth:api'])->group(function () {
    // Get available delivery workers
    Route::get('/delivery-workers/available', [DeliveryWorkerController::class, 'getAvailableWorkers']);
    
    // Assign worker to order
    Route::post('/delivery-workers/assign', [DeliveryWorkerController::class, 'assignToOrder']);
});



