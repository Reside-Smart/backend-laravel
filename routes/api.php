<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserAuthController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ListingDiscountController;
use App\Http\Controllers\Api\RentalOptionsController;
use App\Http\Controllers\Api\ReviewsController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;


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
    Route::post('/listings-update-draft/{id}', [ListingController::class, 'updateAsDraft']);
    Route::post('/listings-update-published/{id}', [ListingController::class, 'updateAsPublish']);
    Route::get('/listings/search', [ListingController::class, 'search']);
    Route::delete('/delete-listing/{listing}', [ListingController::class, 'destroy']);
    Route::get('listings/filter', [ListingController::class, 'filter']);
    Route::get('/top-locations', [ListingController::class, 'getTopLocations']);
    Route::get('/top-agents', [ListingController::class, 'getTopAgents']);



    Route::get('user/listings', [ListingController::class, 'userListings']);
    Route::delete('listings-delete/{listing}', [ListingController::class, 'destroy']);
    Route::get('show-single-listing/{listing}', [ListingController::class, 'showSingleListing']);

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/nearby-estates', [ListingController::class, 'getNearbyLocations']);

    Route::get('/listing-discounts', [ListingDiscountController::class, 'getAllListingDiscounts']);
    Route::get('/user-listing-discounts', [ListingDiscountController::class, 'userListingDiscounts']);
    Route::post('/add-discounts', [ListingDiscountController::class, 'addDiscount']);
    Route::delete('/delete-discount/{discount}', [ListingDiscountController::class, 'deleteDiscount']);

    Route::get('/favorites', [FavoriteController::class, 'getUserFavorites']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{listing}', [FavoriteController::class, 'destroy']);

    Route::delete('/deleteImage/{listing}', [ListingController::class, 'deleteListingImage']);

    Route::post('/cancel-rental-option/{rentalOption}', [RentalOptionsController::class, 'cancelRentalOption']);
    Route::post('/update-rental-options/{rentalOption}', [RentalOptionsController::class, 'updateRentalOption']);
    Route::post('/add-rental-option', [RentalOptionsController::class, 'addRentalOption']);
    Route::get('/listing-rental-options/{listing}', [RentalOptionsController::class, 'getByListing']);

    Route::post('/add-transaction', [TransactionController::class, 'createTransaction']);
    Route::get('/booked-dates/{listingId}', [TransactionController::class, 'getBookedDates']);
    Route::get('/transactions', [TransactionController::class, 'getTransactions']);
    Route::get('/single-transaction/{transaction}', [TransactionController::class, 'getSingleTransaction']);
    Route::post('/mark-as-paid/{transaction}', [TransactionController::class, 'markAsPaid']);

    Route::post('/ratings', [RatingController::class, 'store']);
    Route::get('/show-ratings/{listingId}', [RatingController::class, 'showRatedBefore']);
    Route::get('/show-all-ratings/{listingId}', [RatingController::class, 'getRatingsByListing']);
    Route::post('/reviews', [ReviewsController::class, 'store']);
    Route::get('/user-reviews/{listingId}', [ReviewsController::class, 'userReviews']);
    Route::get('/get-reviews/{listingId}', [ReviewsController::class, 'getReviews']);
    Route::put('/edit-reviews/{id}', [ReviewsController::class, 'update']);
    Route::delete('/delete-reviews/{id}', [ReviewsController::class, 'destroy']);

    // Dashboard analytics endpoints
    Route::prefix('dashboard')->group(function () {
        Route::get('/overview', [DashboardController::class, 'userOverview']);
        Route::get('/revenue', [DashboardController::class, 'revenueOverTime']);
        Route::get('/listing-performance', [DashboardController::class, 'listingPerformance']);
        Route::get('/activity', [DashboardController::class, 'activityBreakdown']);
        Route::get('/categories', [DashboardController::class, 'categoryDistribution']);
        Route::get('/transactions', [DashboardController::class, 'transactionAnalysis']);
        Route::get('/spending', [DashboardController::class, 'spendingAnalysis']);
        Route::get('/rental-analytics', [DashboardController::class, 'rentalPerformance']);
        Route::get('/reviews', [DashboardController::class, 'reviewAnalytics']);
        Route::get('/discounts', [DashboardController::class, 'discountPerformance']);
    });
});

Route::post('/forget-password', [UserAuthController::class, 'forgetPassword'])
    ->name('password.reset');
