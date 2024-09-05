<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Lender;

use Illuminate\Http\Request;
use App\Models\Borrower;
use Illuminate\Support\Facades\Auth;



class LoanController extends Controller
{
    public function index()
    {
        return Loan::with(['lender.user', 'borrower.user'])->get();
    }

    public function getMyLoanRequests()
    {
     
       
            $authUserId= Auth::user()->id;
          return  Loan::with(['lender.user', 'borrower.user'])
            ->whereHas('borrower', function($query) use ($authUserId) {
                $query->where('user_id', '=', $authUserId);
            })
            ->get();
    }

    public function getClientLoanRequests()
    {
      
        $authUserId= Auth::user()->id;
        return  Loan::with(['lender.user', 'borrower.user'])
          ->whereHas('lender', function($query) use ($authUserId) {
              $query->where('user_id', '=', $authUserId);
          })
          ->get();
    }


    public function store(Request $request)
    {
        try {
            // Check if the user is authenticated
            if (Auth::check()) {
                // Check if a Borrower record exists for the authenticated user
                if (Borrower::where('user_id', Auth::user()->id)->exists()) {
                    $borrower = Borrower::where('user_id', Auth::user()->id)->firstOrFail();
                } else {
                    // Create a new Borrower record for the authenticated user
                    $borrower = Borrower::create([
                        'user_id' => Auth::user()->id,
                    ]);
                }
    
                // Create a new Loan record using borrower_id
                $loan = Loan::create([
                    'lender_id' => $request->lender_id,
                    'borrower_id' => $borrower->id,
                    'amount' => $request->amount,
                    'repay_amount' => $request->repay_amount,
                    'interest_rate' => 30,
                    'borrowed_at' => now(),
                    'due_at' => now()->addDays(30),
                ]);
    
                return response()->json(['message' => 'Loan request sent successfully'], 200);
            } else {
                // Handle case where user is not authenticated
                return response()->json(['error' => 'User is not authenticated'], 401);
            }
        } catch (\Throwable $th) {
            // Handle any unexpected errors
            return response()->json(['error' => 'Failed to process loan request'], 500);
        }
    }
    

    public function show($id)
    {
        return Loan::with(['lender.user', 'borrower.user'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'returned_at' => 'nullable|date',
        ]);

        $loan->update($validated);

        return $loan;
    }

    public function approveLoan(Request $request)
{
    try {
       
        $loan = Loan::findOrFail($request->id);
        $lender= Lender::findOrFail($loan->lender_id);

        $lender->update([
            'balance' => $lender->balance-$loan->amount,
        ]);
       
        $loan->update([
            'status' => 'approved',
        ]);

        // Return the updated loan
        return response()->json($loan, 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // If the loan is not found, return a 404 error
        return response()->json(['error' => 'Loan not found'], 404);
    } catch (\Exception $e) {
        // If any other error occurs, return a 500 error
        return response()->json(['error' => 'An error occurred while approving the loan'], 500);
    }
}


    public function rejectLoan(Request $request)
    {
        try {
            // Attempt to find the loan by ID
            $loan = Loan::findOrFail($request->id);
    
            // Update the loan status to 'approved'
            $loan->update([
                'status' => 'rejected',
            ]);
    
            // Return the updated loan
            return response()->json($loan, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If the loan is not found, return a 404 error
            return response()->json(['error' => 'Loan not found'], 404);
        } catch (\Exception $e) {
            // If any other error occurs, return a 500 error
            return response()->json(['error' => 'An error occurred while approving the loan'], 500);
        }
    }

    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return response()->noContent();
    }
}