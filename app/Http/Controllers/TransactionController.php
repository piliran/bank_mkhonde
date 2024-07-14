<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        return Transaction::with('loan')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric',
            'fee' => 'required|numeric',
        ]);

        return Transaction::create($validated);
    }

    public function show($id)
    {
        return Transaction::with('loan')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'fee' => 'required|numeric',
        ]);

        $transaction->update($validated);

        return $transaction;
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return response()->noContent();
    }
}