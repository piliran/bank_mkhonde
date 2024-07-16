<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LenderController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UsersController;


Route::apiResource('transactions', TransactionController::class);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
Route::apiResource('loans', LoanController::class);

Route::apiResource('borrowers', BorrowerController::class);
    Route::post('/logout', [UsersController::class, 'logout']);   
    Route::get('/getWallet', [LenderController::class, 'getWallet']);   
    Route::get('/getMyLoanRequests', [LoanController::class, 'getMyLoanRequests']);   
Route::post('/rejectLoan', [LoanController::class, 'rejectLoan']);
Route::post('/approveLoan', [LoanController::class, 'approveLoan']);

    Route::get('/getClientLoanRequests', [LoanController::class, 'getClientLoanRequests']);   
    Route::apiResource('lenders', LenderController::class);

    });
Route::post('/login', [UsersController::class, 'login']);
Route::post('/register', [UsersController::class, 'register']);
Route::post('/users', [UsersController::class, 'users']);
