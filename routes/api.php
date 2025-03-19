<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserAuthController;


Route::post('/register', [UserAuthController::class, 'register']);
Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/logout', [UserAuthController::class, 'logout']);
Route::post('/email/verify', [UserAuthController::class, 'verifyEmail']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/complete-profile', [UserAuthController::class, 'completeProfile']);
    Route::get('/me', [UserAuthController::class, 'me']);
});

Route::post('/forget-password', [UserAuthController::class, 'forgetPassword'])
    ->name('password.reset');
