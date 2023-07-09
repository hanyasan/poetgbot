<?php

use App\Http\Controllers\Telegram\Hook\HookController;
use Illuminate\Support\Facades\Route;

Route::post('telegram', [HookController::class, 'handle']);
