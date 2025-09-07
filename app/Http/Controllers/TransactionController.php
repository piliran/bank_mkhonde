<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $transactions = Transaction::with(['loan', 'lender', 'borrower'])
                ->where(function($query) use ($user) {
                    $query->where('lender_id', $user->id)
                          ->orWhere('borrower_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($transactions, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching transactions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch transactions'], 500);
        }
    }

    public function createDisbursementTransaction(Loan $loan, array $paymentData): Transaction
    {
        return DB::transaction(function () use ($loan, $paymentData) {
            // Calculate transaction fee (2% of loan amount)
            $transactionFee = $loan->amount * 0.02;
            $netAmount = $loan->amount - $transactionFee;

            $transaction = Transaction::create([
                'loan_id' => $loan->id,
                'lender_id' => $loan->lender_id,
                'borrower_id' => $loan->borrower_id,
                'amount' => $loan->amount,
                'transaction_fee' => $transactionFee,
                'net_amount' => $netAmount,
                'type' => 'disbursement',
                'status' => 'pending',
                'payment_method' => $paymentData['payment_method'] ?? 'bank_transfer',
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'description' => "Loan disbursement for {$loan->amount}",
                'metadata' => $paymentData
            ]);

            return $transaction;
        });
    }

    public function processMobileMoneyPayment(Transaction $transaction, array $paymentData): JsonResponse
    {
        try {
            DB::beginTransaction();

            $transaction->update([
                'status' => 'processing',
                'processed_at' => now(),
                'payment_method' => $paymentData['payment_method'],
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'metadata' => array_merge($transaction->metadata ?? [], $paymentData)
            ]);

            // Here you would integrate with your mobile money API (MPesa, Airtel Money, etc.)
            $paymentResult = $this->processMobileMoneyAPI($transaction, $paymentData);

            if ($paymentResult['success']) {
                $transaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'payment_reference' => $paymentResult['reference']
                ]);

                // Update loan status to active
                $transaction->loan->update(['status' => 'active']);

                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'transaction_id' => $transaction->id,
                    'reference' => $paymentResult['reference'],
                    'message' => 'Payment processed successfully'
                ]);
            } else {
                $transaction->update(['status' => 'failed']);
                DB::commit();
                
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message'] ?? 'Payment failed'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing mobile money payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment'
            ], 500);
        }
    }

    public function processBankTransfer(Transaction $transaction, array $paymentData): JsonResponse
    {
        try {
            DB::beginTransaction();

            $transaction->update([
                'status' => 'processing',
                'processed_at' => now(),
                'payment_method' => 'bank_transfer',
                'payment_reference' => $paymentData['reference'] ?? null,
                'metadata' => array_merge($transaction->metadata ?? [], $paymentData)
            ]);

            // Simulate bank transfer processing
            $paymentSuccess = $this->simulateBankTransfer($transaction, $paymentData);

            if ($paymentSuccess) {
                $transaction->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                $transaction->loan->update(['status' => 'active']);

                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'transaction_id' => $transaction->id,
                    'message' => 'Bank transfer processed successfully'
                ]);
            } else {
                $transaction->update(['status' => 'failed']);
                DB::commit();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Bank transfer failed'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing bank transfer: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bank transfer'
            ], 500);
        }
    }

    private function processMobileMoneyAPI(Transaction $transaction, array $paymentData): array
    {
        // This is where you would integrate with your actual mobile money API
        // For now, we'll simulate a successful payment 90% of the time
        
        $success = rand(1, 100) <= 90;
        
        if ($success) {
            return [
                'success' => true,
                'reference' => 'MM' . now()->timestamp . rand(1000, 9999)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Mobile money payment failed'
        ];
    }

    private function simulateBankTransfer(Transaction $transaction, array $paymentData): bool
    {
        // Simulate bank transfer processing - 95% success rate
        return rand(1, 100) <= 95;
    }

    public function getTransactionStatus($transactionId): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);
            
            return response()->json([
                'status' => $transaction->status,
                'processed_at' => $transaction->processed_at,
                'completed_at' => $transaction->completed_at,
                'payment_method' => $transaction->payment_method,
                'payment_reference' => $transaction->payment_reference
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
    }

    public function getLoanTransactions($loanId): JsonResponse
    {
        try {
            $user = Auth::user();
            $transactions = Transaction::with(['lender', 'borrower'])
                ->where('loan_id', $loanId)
                ->where(function($query) use ($user) {
                    $query->where('lender_id', $user->id)
                          ->orWhere('borrower_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($transactions, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching loan transactions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch transactions'], 500);
        }
    }

    public function cancelTransaction($transactionId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($transactionId);
            
            if ($transaction->status !== 'pending') {
                return response()->json(['error' => 'Only pending transactions can be cancelled'], 400);
            }

            $transaction->update(['status' => 'cancelled']);

            if ($transaction->type === 'disbursement') {
                $transaction->loan->update(['status' => 'pending']);
            }

            DB::commit();
            return response()->json(['message' => 'Transaction cancelled successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling transaction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel transaction'], 500);
        }
    }
}

//test