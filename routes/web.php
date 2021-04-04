<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return Inertia\Inertia::render('Dashboard');
})->name('dashboard');
Route::get('testJd',[TestController::class,'JD']);
Route::get('createOrder',[TestController::class,'createOrder']);


Route::prefix('product')->group(function(){
    Route::match(['get','post'],'',[ProductController::class])->name('');
    Route::get('detail/{stockId}',[ProductController::class])->name('product.detail');
});
Route::prefix('order')->group(function() {
    Route::match(['post', 'get'], '', [OrderController::class, ''])->name('order.list');
    Route::get('detail/{orderSn}', [OrderController::class, ''])->name('');
    Route::post('createOrder',[OrderController::class,'createOrder'])->name('order.create');
    Route::get('test',[TestController::class,'test']);

});
Route::prefix('cart')->group(function() {
    Route::get('list', [CartController::class, 'getList'])->name('cart.list');//购物车详情
    Route::post('commit', [CartController::class, 'commitCart'])->name('cart.commit');//提交购物车
    Route::post('change', [CartController::class, 'change'])->name('cart.change');//编辑购物车
});
Route::prefix('auth')->group(function(){
    Route::view('login','');
    Route::view('forget','');
    Route::post('login',[]);
});






