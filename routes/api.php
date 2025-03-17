<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserAuthController;


Route::post('/register', [UserAuthController::class, 'register']);
Route::post('/email/verify', [UserAuthController::class, 'verifyEmail']);
Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/logout', [UserAuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/complete-profile', [UserAuthController::class, 'completeProfile']);
});

Route::post('/forget-password', [UserAuthController::class, 'forgetPassword']);
Route::post('/reset-password', [UserAuthController::class, 'resetPassword'])->name('password.reset');
