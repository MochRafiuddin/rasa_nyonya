<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CAAuth;

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
});