<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\LenderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class LenderController extends Controller
{
    // public function index(): JsonResponse
    // {
    //     $lenders = User::whereHas('accountType', function ($query) {
    //         $query->where('type', 'lender');
    //     })->with('banks')->get();

    //     return response()->json(LenderResource::collection($lenders), 200);
    // }

    public function index(): JsonResponse
{
    // Get the authenticated borrower (user)
    $borrowerId =  Auth::user()->id; 

    // Retrieve all lenders and their banks, and add the subscription status
    $lenders = User::whereHas('accountType', function ($query) {
        $query->where('type', 'lender');
    })
    ->with('banks')
    ->get()
    ->map(function ($lender) use ($borrowerId) {
        // Check if the borrower is subscribed to the lender
        $isSubscribed = \App\Models\Subscription::where('lender_id', $lender->id)
                            ->where('borrower_id', $borrowerId)
                            ->exists();

        // Add the subscription status to the lender data
        $lender->is_subscribed = $isSubscribed;

        return $lender;
    });

    // Return the lenders with subscription status
    return response()->json(LenderResource::collection($lenders), 200);
}

}
