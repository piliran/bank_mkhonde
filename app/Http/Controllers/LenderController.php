<?php

namespace App\Http\Controllers;

use App\Models\Lender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LenderController extends Controller
{
    public function index()
{
   
    return Lender::with('user')
                 ->where('user_id', '!=', Auth::user()->id)
                 ->get();

                //  return Lender::with('user') ->get();
              
}


    public function getWallet()
    {
        try {
        return Lender::where('user_id', Auth::user()->id)->firstOrFail();
        // return response()->json(['balance' => $lender->balance], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found or balance not available'], 404);
        }
    }

   public function store(Request $request)
   {
       if (Lender::where('user_id', Auth::user()->id)->exists()) {
           $lender = Lender::where('user_id', Auth::user()->id)->firstOrFail();
           $lender->balance += $request->amount;
           $lender->minimum = $request->minimum_loan_amount;
           $lender->interest = $request->interest;
           $lender->payment_duration = $request->payment_duration;
           $lender->save();
       } else {
           Lender::create([
               'user_id' => Auth::user()->id,
               'balance' => $request->amount,
               'minimum' => $request->minimum_loan_amount,
               'interest' => $request->interest,
               'payment_duration' => $request->payment_duration,
           ]);
       }
       
       return response()->json(['message' => 'Amount deposited successfully'], 200);
   }
   

    public function show($id)
    {
        return Lender::with('user')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $lender = Lender::findOrFail($id);

        $validated = $request->validate([
            'balance' => 'required|numeric',
        ]);

        $lender->update($validated);

        return $lender;
    }

    public function destroy($id)
    {
        $lender = Lender::findOrFail($id);
        $lender->delete();

        return response()->noContent();
    }
}