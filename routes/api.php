<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserAuthController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ListingDiscountController;

Route::post('/register', [UserAuthController::class, 'register']);
Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/email/verify', [UserAuthController::class, 'verifyEmail']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserAuthController::class, 'logout']);
    Route::post('/complete-profile', [UserAuthController::class, 'completeProfile']);
    Route::get('/me', [UserAuthController::class, 'me']);
    Route::put('/user/change-password', [UserAuthController::class, 'changePassword']);
    Route::post('/user/edit-profile', [UserAuthController::class, 'editProfile']);

    Route::post('/listings-draft', [ListingController::class, 'saveAsDraft']);
    Route::post('/listings-published', [ListingController::class, 'saveAsPublished']);
    Route::put('/listings-update-draft/{id}', [ListingController::class, 'updateAsDraft']);
    Route::put('/listings-update-published/{id}', [ListingController::class, 'updateAsPublish']);
    Route::get('/listings/search', [ListingController::class, 'search']);

    Route::get('user/listings', [ListingController::class, 'userListings']);
    Route::delete('listings-delete/{listing}', [ListingController::class, 'destroy']);
    Route::get('show-single-listing/{listing}', [ListingController::class, 'showSingleListing']);

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/nearby-estates', [ListingController::class, 'getNearbyLocations']);

    Route::get('/listing-discounts', [ListingDiscountController::class, 'getAllListingDiscounts']);

    Route::get('/favorites', [FavoriteController::class, 'getUserFavorites']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{listing}', [FavoriteController::class, 'destroy']);
});

Route::post('/forget-password', [UserAuthController::class, 'forgetPassword'])
    ->name('password.reset');
