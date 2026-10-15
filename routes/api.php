<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\League\LeagueController;
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
Route::group(['prefix' => 'v1', 'middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'league'], function () {
        Route::get('/',  [LeagueController::class, 'index']);
    });

    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login',  [AuthController::class, 'login']);

        Route::post('/register',  [AuthController::class, 'register']);

        Route::post('/logout',  [AuthController::class, 'logout']);

        Route::post('/refresh',  [AuthController::class, 'refresh']);
    });
    //TODO: now disabled
//    Route::group(['prefix' => 'price' ], function () {
//        Route::get('currency/{ninjaDetailsId}', [CurrencyController::class, 'showPrice']);
//    });

    //TODO: tg now disabled
//    Route::group(['prefix' => 'telegram' ], function () {
//        Route::get('set', [TelegramBotController::class, 'set']);
//    });
});
