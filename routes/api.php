<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LenderController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\CollateralController;
use App\Http\Controllers\LoanRequestController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubscriptionController;  // Make sure to import this
use App\Http\Controllers\UserController;  // Make sure to import this
use App\Http\Controllers\TransactionController; // Import TransactionController
use Illuminate\Support\Facades\Broadcast; // Import Broadcast facade
use Illuminate\Support\Facades\Log; // Import Log facade
// routes/api.php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Models\LoanRequest;
use App\Models\Chat;

use App\Events\LoanRequestCreated;
use App\Events\NewNotificationEvent;



// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});


// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}/status', [TransactionController::class, 'getTransactionStatus']);
    Route::get('/loans/{loanId}/transactions', [TransactionController::class, 'getLoanTransactions']);
    Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancelTransaction']);
    Route::post('/transactions/{id}/process-mobile-money', [TransactionController::class, 'processMobileMoneyPayment']);
    Route::post('/transactions/{id}/process-bank-transfer', [TransactionController::class, 'processBankTransfer']);

    // Add this route for loan repayments
Route::post('/loans/{id}/repay', [LoanController::class, 'repay']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/upload_profile_picture', [AuthController::class, 'upload_profile_picture']);
    Route::post('/personal-info', [AuthController::class, 'updatePersonalInfo']);
    Route::get('/user-info', [AuthController::class, 'getPersonalInfo']);
    Route::get('/user/status/{id}', [AuthController::class, 'status']);
    Route::post('/user/update_last_seen', [AuthController::class, 'updateLastSeen']);
    Route::post('/user/update_status', [AuthController::class, 'updateUserStatus']);

    // Lenders
    Route::get('/lenders', [LenderController::class, 'index']);

    // Collaterals
    Route::post('/collateral', [CollateralController::class, 'upload']);
    Route::get('/collaterals', [CollateralController::class, 'index']);
    Route::get('/collaterals/available', [CollateralController::class, 'available']);

    // Banks
    Route::post('/banks', [BankController::class, 'store']);
    Route::get('/banks', [BankController::class, 'index']);
    Route::get('/lender_banks/{id}', [BankController::class, 'loadLenderBanks']);
    Route::post('/banks/update/{id}', [BankController::class, 'updateBank']);

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
    Route::get('/seized-collateral', [LoanController::class, 'seizedCollateral']);
    Route::post('/loan/{id}/square', [LoanController::class, 'squareLoan']);

    Route::post('/chat/start', [ChatController::class, 'startChat']);
    Route::get('/chat/{chatId}/messages', [ChatController::class, 'getMessages']);
    Route::get('/chat/{chatId}/read_messages', [ChatController::class, 'readMessages']);
    Route::post('/message/send', [MessageController::class, 'sendMessage']);
    Route::get('chat/list/{id}', [ChatController::class, 'listChats']);
    Route::get('unread_message_count', [ChatController::class, 'unReadMessageCount']);
    Route::get('/get_user_chats', [ChatController::class, 'getUserChats']);


    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::get('/test-broadcast', function () {
    // $loanRequest = LoanRequest::first();

    Log::info('Testing broadcasting');

    // broadcast(new LoanRequestCreated($loanRequest));


    broadcast(new NewNotificationEvent('Hello from Laravel!'));

    return 'Broadcasting test';
});
