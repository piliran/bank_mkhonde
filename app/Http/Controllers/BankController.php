<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    public function index(): JsonResponse
    {
        $banks = Bank::where('user_id', Auth::id())->get();

        return response()->json($banks, 200);
    }

    public function loadLenderBanks($lender_id): JsonResponse
    {
        $banks = Bank::where('user_id', $lender_id)->get();

        return response()->json($banks, 200);
    }


     // Add a new bank
     public function store(Request $request)
     {
         $validatedData = $request->validate([
             'bank_name' => 'required|string|max:255',
             'account_number' => 'required|string|max:20',
         ]);
 
         $bank = Bank::create([
             'user_id' => Auth::id(),
             'bank_name' => $validatedData['bank_name'],
             'account_number' => $validatedData['account_number'],
         ]);
 
         return response()->json($bank, 201);
     }
 
     // Edit a bank
     public function updateBank(Request $request, $id)
     {
         $validatedData = $request->validate([
             'bank_name' => 'required|string|max:255',
             'account_number' => 'required|string|max:20',
         ]);
 
         $bank = Bank::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
         $bank->update($validatedData);
 
         return response()->json($bank);
     }
}
