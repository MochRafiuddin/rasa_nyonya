<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CAAuth;
use App\Http\Controllers\Api\CADelivery;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::group(['middleware' => 'logapi'], function () {	
    Route::post('/auth-signin', [CAAuth::class, 'login']);
});

Route::group(['middleware' => 'myauth'], function () {	
    Route::post('/ubah-password', [CAAuth::class, 'ubah_password']);
    Route::post('/auth-signout', [CAAuth::class, 'logout']);
    Route::get('/detail-profil', [CAAuth::class, 'detail_profil']);    
    Route::get('/get-all-delivery', [CADelivery::class, 'get_all_delivery']);    
    Route::post('/post-pickup', [CADelivery::class, 'post_pickup']);
    Route::get('/get-all-pickup', [CADelivery::class, 'get_all_pickup']);
    Route::post('/post-done-pickup', [CADelivery::class, 'post_done_pickup']);
    Route::get('/get-delivery-fee-history', [CADelivery::class, 'get_delivery_fee_history']);
    Route::get('/get-total-fee-today', [CADelivery::class, 'get_total_fee_today']);
});