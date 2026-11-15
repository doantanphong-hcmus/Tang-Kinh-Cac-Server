<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\RegisterController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn (): JsonResponse => response()->json([
    'data' => [
        'status' => 'ok',
    ],
]))->name('health');

Route::post('/auth/register', RegisterController::class)
    ->middleware('throttle:register')
    ->name('auth.register');

Route::post('/auth/login', LoginController::class)
    ->middleware('throttle:login')
    ->name('auth.login');
