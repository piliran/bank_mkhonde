<?php
namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\Collateral;
use App\Models\SeizedCollateral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\LoanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use App\Events\LoanCleared;
use App\Events\LoanUpdated;
use App\Events\CollateralUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LoanControllercopy extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $Loans = Loan::with(['borrower', 'lender', 'collateral'])->where('borrower_id', $user->id)
            ->orWhere('lender_id', $user->id)
            ->get();

        return response()->json(LoanResource::collection($Loans), 200);
    }


    public function seizedCollateral(): JsonResponse
{
    $user = Auth::user();

    $seizedCollaterals = SeizedCollateral::with(['collateral', 'borrower'])
        ->where('lender_id', $user->id)
        ->where('status', 'held') // Only currently held collaterals
        ->get()
        ->map(function ($seized) {
            return [
                'id' => $seized->id,
                'collateral' => $seized->collateral,
                'borrower' => $seized->borrower,
                'seized_at' => $seized->seized_at,
                'reason' => $seized->reason,
            ];
        });

    return response()->json($seizedCollaterals, 200);
}

    // public function seizedCollateral(): JsonResponse
    // {
    //     $user = Auth::user();

    //     $loans = Loan::with(['borrower', 'collateral'])
    //         ->where('lender_id', $user->id)
    //         ->whereHas('collateral', function($query) {
    //             $query->where('status', 'seized'); 
    //         })
    //         ->get();

    //     return response()->json($loans, 200);
    // }

    public function repay(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $loan = Loan::with(['borrower', 'lender', 'collateral'])->findOrFail($id);
            $user = Auth::user();

            // Validate that the authenticated user is the borrower
            if ($loan->borrower_id !== $user->id) {
                return response()->json(['error' => 'You are not authorized to repay this loan.'], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:bank_transfer,mpamba,airtel_money,cash',
                'payment_reference' => 'nullable|string|max:255',
                'is_partial' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            $amount = (float) $request->amount;
            $isPartial = $request->is_partial ?? false;

            // Check if loan can be repaid
            if ($loan->status !== 'active') {
                return response()->json(['error' => 'This loan is not active and cannot be repaid.'], 400);
            }

            if ($amount > $loan->repayment_amount) {
                return response()->json(['error' => 'Repayment amount cannot exceed the total loan amount.'], 400);
            }

            // Calculate transaction fee (1% for repayments)
            $transactionFee = $amount * 0.01;
            $netAmount = $amount - $transactionFee;

            // Create repayment transaction
            $transaction = Transaction::create([
                'loan_id' => $loan->id,
                'lender_id' => $loan->lender_id,
                'borrower_id' => $loan->borrower_id,
                'amount' => $amount,
                'transaction_fee' => $transactionFee,
                'net_amount' => $netAmount,
                'type' => 'repayment',
                'status' => 'completed',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'description' => "Loan repayment for MWK " . number_format($amount, 2),
                'metadata' => $request->all(),
                'completed_at' => now(),
            ]);

            // Update loan repayment amount
            $loan->repayment_amount -= $amount;
            
            // Check if loan is fully paid
            if ($loan->repayment_amount <= 0) {
                $this->squareLoanAutomatically($loan);

                 broadcast(new LoanCleared($loan->borrower_id, "Loan has been fully paid"));
            broadcast(new LoanCleared($loan->lender_id, "Loan has been fully paid by {$loan->borrower->first_name}"));
            
            // Broadcast collateral updated event
            if ($loan->collateral) {
                broadcast(new CollateralUpdated($loan->collateral));
            }
            } else {
                $loan->save();
                 broadcast(new LoanUpdated($loan));
            }

            DB::commit();

            return response()->json([
                'message' => 'Repayment processed successfully',
                'transaction_id' => $transaction->id,
                'remaining_balance' => max(0, $loan->repayment_amount),
                'loan_status' => $loan->status,
                'is_fully_paid' => $loan->repayment_amount <= 0
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loan repayment error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Automatically square the loan when fully paid
     */
    private function squareLoanAutomatically(Loan $loan): void
    {
        // Update loan status to 'paid'
        $loan->status = 'paid';
        $loan->repayment_amount = 0;
        $loan->date_repaid = now();
        $loan->save();

        // Update collateral status if it exists
        if ($loan->collateral) {
            $collateral = $loan->collateral;
            $collateral->status = 'available';
            $collateral->save();
        }

        // Send push notification to both borrower and lender
        $this->sendLoanClearedNotifications($loan);

        // Create notifications
        $this->createLoanClearedNotifications($loan);

        // Send WebSocket notification
        $this->sendWebSocketNotification($loan->borrower_id, 
            'Your loan of MWK ' . number_format($loan->amount, 2) . ' has been fully paid.'
        );
        
        // Also notify lender
        $this->sendWebSocketNotification($loan->lender_id,
            'Loan of MWK ' . number_format($loan->amount, 2) . ' has been fully paid by ' . $loan->borrower->first_name . ' ' . $loan->borrower->last_name
        );
    }

    /**
     * Send push notifications for loan clearance
     */
    private function sendLoanClearedNotifications(Loan $loan): void
    {
        // Notify borrower
        if ($loan->borrower->expo_push_token) {
            $this->sendPushNotification(
                $loan->borrower->expo_push_token,
                'Loan Paid Successfully',
                'Your loan of MWK ' . number_format($loan->amount, 2) . ' has been fully paid. Collateral released.'
            );
        }

        // Notify lender
        if ($loan->lender->expo_push_token) {
            $this->sendPushNotification(
                $loan->lender->expo_push_token,
                'Loan Fully Paid',
                'Loan of MWK ' . number_format($loan->amount, 2) . ' has been fully paid by ' . $loan->borrower->first_name . ' ' . $loan->borrower->last_name
            );
        }
    }

    /**
     * Create database notifications for loan clearance
     */
    private function createLoanClearedNotifications(Loan $loan): void
    {
        // Notification for borrower
        Notification::create([
            'recipient_id' => $loan->borrower_id,
            'message' => 'Your loan of MWK ' . number_format($loan->amount, 2) . ' has been fully paid. Collateral has been released.'
        ]);

        // Notification for lender
        Notification::create([
            'recipient_id' => $loan->lender_id,
            'message' => 'Loan of MWK ' . number_format($loan->amount, 2) . ' has been fully paid by ' . $loan->borrower->first_name . ' ' . $loan->borrower->last_name
        ]);
    }

    /**
     * Keep the old squareLoan method for backward compatibility
     * (in case lenders still need to manually square for some reason)
     */
    public function squareLoan(Request $request, $loan_id)
    {
        DB::beginTransaction();

        try {
            $loan = Loan::with(['borrower', 'lender', 'collateral'])->findOrFail($loan_id);

            // Authorization check
            if ($loan->lender_id != auth()->user()->id) {
                return response()->json(['error' => 'You are not authorized to square this loan.'], 403);
            }

            // Check loan status
            if ($loan->status != 'active') {
                return response()->json(['error' => 'Cannot clear a loan that is ' . $loan->status . '.'], 400);
            }

            $this->squareLoanAutomatically($loan);

            DB::commit();

            return response()->json(['message' => 'Loan cleared successfully and both parties notified.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error clearing loan: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to clear loan.'], 500);
        }
    }

    public function sendPushNotification($expoPushToken, $title, $body)
    {
        $message = [
            'to' => $expoPushToken,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => ['extra_data' => 'optional_data'],
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $message);

            if ($response->failed()) {
                Log::error('Error sending push notification: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }

    private function sendWebSocketNotification($userId, $message)
    {
        try {
            broadcast(new LoanCleared($userId, $message));
        } catch (\Exception $e) {
            Log::error('WebSocket notification error', ['error' => $e->getMessage()]);
        }
    }
}