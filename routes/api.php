<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\GenreController;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\LogoutController;
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

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', CurrentUserController::class)->name('auth.me');
    Route::post('/auth/logout', LogoutController::class)->name('auth.logout');
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/{genre}/books', [GenreController::class, 'books'])->name('genres.books.index');
});
