<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('product')->group(function() {
    Route::get('detail/{stockId}', [ProductController::class, 'detail']);
    Route::post('change', [ProductController::class, 'change']);
    Route::post('list',[ProductController::class,'getList']);
});
Route::prefix('cart')->group(function(){
    Route::get('list',[CartController::class,'getList']);
    Route::post('change',[CartController::class,'change']);
    Route::post('commit',[CartController::class,'commitCart']);
});
Route::prefix('user')->middleware('')->group(function(){
    Route::get('info',[UserController::class,'info']);
    Route::get('address/{id}',[UserController::class,'address']);
    Route::get('addressList',[UserController::class,'addressList']);
});
Route::prefix('order')->group(function() {
    Route::match(['POST', 'GET'], 'notifyWeChat', [OrderController::class, 'notifyWeChat']);
    Route::match(['POST', 'GET'], 'notifyAliPay', [OrderController::class, 'notifyAliPay']);

//    Route::middleware('auth.jwt')->group(function () {

    Route::post('create', [OrderController::class, 'createOrder']);//创建订单
    Route::get('check/{orderSn}', [OrderController::class, 'checkOrder']);//确认订单
    Route::get('detail/{orderSn}', [OrderController::class, 'detailOrder']);//订单详情
    Route::get('cancel/{orderSn}', [OrderController::class, 'cancelOrder']);//取消订单
    Route::post('list', [OrderController::class, 'listOrder']);//订单列表
    Route::get('confirm/{orderSn}', [OrderController::class, 'confirmOrder']);//确认订单
    Route::delete('delete/{orderSn}', [OrderController::class, 'delOrder']);
    Route::post('pay', [OrderController::class, 'payOrder']);
//    });
});
