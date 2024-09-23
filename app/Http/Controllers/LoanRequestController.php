<?php
namespace App\Http\Controllers;

use App\Models\Notification;

use App\Models\LoanRequest;
use App\Models\Collateral;
use App\Http\Requests\LoanRequestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\LoanRequestResource;
use App\Events\LoanRequestCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Events\LoanRequestApproved;
use App\Events\LoanRejected;
use App\Models\Loan;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;



class LoanRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $loanRequests = LoanRequest::with(['borrower','lender','collateral'])->where('borrower_id', $user->id)
            ->orWhere('lender_id', $user->id)
            ->get();

        return response()->json($loanRequests, 200);

        // $loanRequests = LoanRequest::with(['borrower', 'lender', 'collateral'])->get();
        // return LoanRequestResource::collection($loanRequests);
    }

 

    public function store(LoanRequestRequest $request): JsonResponse
    {
        DB::beginTransaction();
    
        try {
            // Create a new LoanRequest
            $loanRequest = new LoanRequest;
            $loanRequest->borrower_id = Auth::id();
            $loanRequest->lender_id = $request->lender_id;
            $loanRequest->amount = $request->amount;
            $loanRequest->repayment_period = $request->repayment_period;
            $loanRequest->interest_rate = $request->interest_rate;
            $loanRequest->collateral_id = $request->collateral_id;
            $loanRequest->save();
    
            // Update collateral status if it exists
            if ($loanRequest->collateral_id) {
                $collateral = Collateral::find($loanRequest->collateral_id);
    
                if ($collateral) {
                    $collateral->status = 'on hold'; // Update status to "on hold"
                    $collateral->save(); // Save the updated collateral
                }
            }
    
            // Commit the transaction if all operations succeed
            DB::commit();
    
            // Additional logic for notifications and websockets can be added here
            // broadcast(new LoanRequestCreated($loanRequest))->toOthers();
            broadcast(new LoanRequestCreated($loanRequest))->toOthers();
            return response()->json($loanRequest, 201);
    
        } catch (\Exception $e) {
            // Rollback the transaction if there is an error
            DB::rollBack();
    
            return response()->json(['error' => 'Failed to create loan request.'], 500);
        }
    }
    
    public function reject(Request $request, $requestId)
    {
        DB::beginTransaction();
    
        try {
            $loanRequest = LoanRequest::find($requestId);
    
            if (!$loanRequest) {
                return response()->json(['error' => 'Loan request not found.'], 404);
            }
    
            if ($loanRequest->lender_id != Auth::id()) {
                return response()->json(['error' => 'You are not authorized to reject this loan request.'], 403);
            }
    
            if ($loanRequest->status != 'pending') {
                return response()->json(['error' => "Cannot reject a loan request that is {$loanRequest->status}."], 400);
            }
    
            // Update loan request status to 'rejected'
            $loanRequest->status = 'rejected';
            $loanRequest->save();
    
            // Update collateral status if it exists
            if ($loanRequest->collateral) {
                $loanRequest->collateral->status = 'available';
                $loanRequest->collateral->save();
            }
    
            // Commit the transaction after successfully updating loan and collateral
            DB::commit();
    
            // Send push notification to the borrower if token exists
            if ($loanRequest->borrower->expo_push_token) {
                $this->sendPushNotification(
                    $loanRequest->borrower->expo_push_token,
                    'Loan request response',
                    "Your loan request for MWK {$loanRequest->amount} has been rejected by {$loanRequest->lender->company_name}."
                );
            }
    
            // Create a notification for the borrower
            Notification::create([
                'recipient_id' => $loanRequest->borrower_id,
                'message' => "Your loan request for MWK {$loanRequest->amount} has been rejected by {$loanRequest->lender->company_name}."
            ]);

            broadcast(new LoanRejected($loanRequest->borrower_id, "Your loan request for MWK {$loanRequest->amount} has been rejected by {$loanRequest->lender->company_name}."));

    
            return response()->json(['message' => 'Loan request rejected successfully and borrower notified.']);
    
        } catch (\Exception $e) {
            // Rollback the transaction if there is an error
            DB::rollBack();
    
            return response()->json(['error' => 'Failed to reject loan request.'], 500);
        }
    }
    


    
    public function accept(Request $request, $requestId)
    {
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Find the loan request
            $loanRequest = LoanRequest::findOrFail($requestId);
            
            // Authorization check
            if ($loanRequest->lender_id != Auth::id()) {
                return response()->json(['error' => 'You are not authorized to accept this loan request.'], 403);
            }
            
            if ($loanRequest->status != 'pending') {
                return response()->json(['error' => "Cannot accept a loan request that is {$loanRequest->status}."], 400);
            }
            
            // Validate repayment amount
            $repaymentAmount = $request->repayment_amount;
            if (!$repaymentAmount) {
                return response()->json(['error' => 'Repayment amount is required.'], 400);
            }
    
            // Validate repayment amount format
            if (!is_numeric($repaymentAmount)) {
                return response()->json(['error' => 'Invalid repayment amount format.'], 400);
            }
    
            // Mark the loan request as approved
            $loanRequest->status = 'approved';
            $loanRequest->save();
    
            // Update collateral status if it exists
            if ($loanRequest->collateral) {
                $loanRequest->collateral->status = 'seized';
                $loanRequest->collateral->save();
            }
    
            // Create a Loan entry
            $loan = new Loan();
            $loan->borrower_id = $loanRequest->borrower_id;
            $loan->lender_id = Auth::id();
            $loan->amount = $loanRequest->amount;
            $loan->interest_rate = $loanRequest->interest_rate;
            $loan->repayment_period = $loanRequest->repayment_period;
            $loan->collateral_id = $loanRequest->collateral_id;
            $loan->actual_amount_loaned = $loanRequest->amount;
            $loan->repayment_amount = $repaymentAmount;

            // Save the Loan instance to the database
            $loan->save();
    
            // Send push notification to the borrower if token exists
            if ($loanRequest->borrower->expo_push_token) {
                $this->sendPushNotification(
                    $loanRequest->borrower->expo_push_token,
                    'Loan request approved.',
                    "Your loan request for MWK {$loanRequest->amount} has been approved by {$loanRequest->lender->company_name}."
                );
            }
    
            // Create a notification for the borrower
            Notification::create([
                'recipient_id' => $loanRequest->borrower_id,
                'message' => "Your loan request for MWK {$loanRequest->amount} has been approved by {$loanRequest->lender->company_name}."
            ]);
    
            // Send WebSocket notification (implement this in a similar way as the Django method)
            $this->sendWebSocketNotification($loanRequest->borrower_id, "Your loan request for MWK {$loanRequest->amount} has been approved by {$loanRequest->lender->company_name}.");
    
            // Commit the transaction
            DB::commit();
    
            return response()->json(['message' => 'Loan request approved successfully and borrower notified.'], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();
            return response()->json(['error' => 'An error occurred while accepting the loan request: ' . $e->getMessage()], 500);
        }
    }
    
    private function sendWebSocketNotification($borrowerId, $message)
    {
        try {
            broadcast(new LoanRequestApproved($borrowerId, $message));
        } catch (\Exception $e) {
            Log::error('WebSocket notification error', ['error' => $e->getMessage()]);
        }
    }
    

    public function withdraw(Request $request, $requestId)
    {
        $loanRequest = LoanRequest::find($requestId);

        if (!$loanRequest) {
            return response()->json(['error' => 'Loan request not found.'], 404);
        }

        if ($loanRequest->borrower_id != Auth::id()) {
            return response()->json(['error' => 'You are not authorized to withdraw this loan request.'], 403);
        }

        if ($loanRequest->status != 'pending') {
            return response()->json(['error' => "Cannot withdraw a loan request that is {$loanRequest->status}."], 400);
        }

        // Update loan request status to 'withdrawn'
        $loanRequest->status = 'withdrawn';
        $loanRequest->save();

        // Update collateral status if it exists
        if ($loanRequest->collateral) {
            $loanRequest->collateral->status = 'available';
            $loanRequest->collateral->save();
        }

        // Send push notification to the lender
        if ($loanRequest->lender->expo_push_token) {
            $this->sendPushNotification(
                $loanRequest->lender->expo_push_token,
                'Loan request withdrawn',
                "{$loanRequest->borrower->first_name} {$loanRequest->borrower->last_name} has withdrawn the loan request."
            );
        }

        // Create a notification for the lender
        Notification::create([
            'recipient_id' => $loanRequest->lender_id,
            'message' => "{$loanRequest->borrower->first_name} {$loanRequest->borrower->last_name} has withdrawn the loan request."
        ]);

        return response()->json(['message' => 'Loan request withdrawn successfully, collateral updated, and lender notified.']);
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
                \Log::error('Error sending push notification: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }


}
