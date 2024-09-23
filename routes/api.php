<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LenderController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\CollateralController;
use App\Http\Controllers\LoanRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubscriptionController;  // Make sure to import this
use App\Http\Controllers\UserController;  // Make sure to import this

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->post('/broadcasting/auth', function () {
    return [];
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Lenders
    Route::get('/lenders', [LenderController::class, 'index']);    

    // Collaterals
    Route::post('/collateral', [CollateralController::class, 'upload']);
    Route::get('/collaterals', [CollateralController::class, 'index']);
    Route::get('/collaterals/available', [CollateralController::class, 'available']);    

    // Loan Requests
    Route::get('/loan-requests', [LoanRequestController::class, 'index']);
    Route::post('/loan-requests', [LoanRequestController::class, 'store']);
    Route::post('/loan-requests/{id}/accept', [LoanRequestController::class, 'accept']);    
    Route::post('/loan-requests/{id}/reject', [LoanRequestController::class, 'reject']);    
    Route::post('/loan-requests/{id}/withdraw', [LoanRequestController::class, 'withdraw']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadNotificationsCount']);

    // Subscriptions
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/subscribe', [SubscriptionController::class, 'subscription']);
    Route::get('/check-subscription/{id}', [SubscriptionController::class, 'checkSubscription']);

    // Expo push token
    Route::post('/save-push-token', [AuthController::class, 'saveExpoToken']);

    // Loans
    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loan/{id}/square', [LoanController::class, 'squareLoan']);

 
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
