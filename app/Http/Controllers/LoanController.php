<?php
namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\LoanResource;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use App\Events\LoanCleared;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;




class LoanController extends Controller
{
   
    public function index(): JsonResponse
    {
        $user = Auth::user();
        // $loans = Loan::where('borrower_id', $user->id)
        //     ->orWhere('lender_id', $user->id)
        //     ->get();

        // return response()->json($loans, 200);
        $Loans = Loan::with(['borrower', 'lender', 'collateral'])->where('borrower_id', $user->id)
        ->orWhere('lender_id', $user->id)
        ->get();

        return response()->json(LoanResource::collection($Loans), 200);


        // return LoanResource::collection($Loans);
    }
  




    


     public function squareLoan(Request $request, $loan_id)
    {
        DB::beginTransaction();

        try {
            // Fetch the loan
            $loan = Loan::findOrFail($loan_id);

            // Authorization check
            if ($loan->lender_id != auth()->user()->id) {
                return response()->json(['error' => 'You are not authorized to square this loan.'], 403);
            }

            // Check loan status
            if ($loan->status != 'active') {
                return response()->json(['error' => 'Cannot clear a loan that is ' . $loan->status . '.'], 400);
            }

            // Update loan status to 'paid'
            $loan->status = 'paid';
            $loan->date_repaid = now();
            $loan->save();

            // Update collateral status if it exists
            if ($loan->collateral) {
                $collateral = $loan->collateral;
                $collateral->status = 'available';
                $collateral->save();
            }

            // Send push notification if the borrower has an Expo token
            if ($loan->borrower->expo_push_token) {
                $this->sendPushNotification(
                    $loan->borrower->expo_push_token,
                    'Loan request response.',
                    'Your loan of MWK ' . $loan->repayment_amount . ' has been cleared by ' . $loan->lender->company_name
                );
            } else {
                Log::info('Borrower has no Expo push token.');
            }

            // Create notification for the borrower
            Notification::create([
                'recipient_id' => $loan->borrower_id,
                'message' => 'Your loan of MWK ' . $loan->repayment_amount . ' has been cleared by ' . $loan->lender->company_name
            ]);

            // Send WebSocket notification
            $this->sendWebSocketNotification($loan->borrower_id, 'Your loan of MWK ' . $loan->repayment_amount . ' has been cleared by ' . $loan->lender->company_name);

            // Commit transaction if everything went well
            DB::commit();

            return response()->json(['message' => 'Loan cleared successfully and borrower notified.'], 200);

        } catch (Exception $e) {
            // Rollback transaction if any exception occurs
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
                \Log::error('Error sending push notification: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }

    private function sendWebSocketNotification($borrowerId, $message)
    {
       
        try {
            broadcast(new LoanCleared($borrowerId, $message));
        } catch (\Exception $e) {
            Log::error('WebSocket notification error', ['error' => $e->getMessage()]);
        }
    }



}
