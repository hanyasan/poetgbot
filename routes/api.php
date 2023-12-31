<?php

use App\Http\Controllers\Price\CurrencyController;
use App\Http\Controllers\Telegram\TelegramBotController;
use Illuminate\Support\Facades\Route;

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

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'price' ], function () {
        Route::get('currency/{ninjaDetailsId}', [CurrencyController::class, 'showPrice']);
    });

    Route::group(['prefix' => 'telegram' ], function () {
        Route::get('set', [TelegramBotController::class, 'set']);
    });
});
