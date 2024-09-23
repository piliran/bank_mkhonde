<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use App\Models\Subscription;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Support\Facades\Auth;
use App\Events\NewNotificationEvent;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $borrower = Auth::user();
        $lenderId = $request->lender_id;
    
        $lender = User::find($lenderId);
    
        if (!$lender) {
            return response()->json(['error' => 'Lender not found'], 404);
        }
    
        $subscription = Subscription::create([
            'borrower_id' => $borrower->id,
            'lender_id' => $lenderId,
        ]);
    
        if ($lender->expo_push_token) {
            $this->sendPushNotification(
                $lender->expo_push_token,
                'New subscriber',
                "{$borrower->first_name} {$borrower->last_name} has subscribed to your account."
            );
        }
        event(new NewNotificationEvent('This is a real-time notification!'));

    
        return response()->json(['message' => 'Subscription created successfully']);
    }
    

    public function checkSubscription($lenderId)
    {
        $borrower = Auth::user();
        $isSubscribed = Subscription::where('borrower_id', $borrower->id)
            ->where('lender_id', $lenderId)
            ->exists();

        return response()->json(['is_subscribed' => $isSubscribed]);
    }


    public function subscription()
    {
        $borrower = Auth::user();
        $subscriptions = Subscription::with(['borrower', 'lender'])->where('borrower_id', $borrower->id)
            ->orWhere('lender_id', $borrower->id)
            ->get();

        return response()->json(SubscriptionResource::collection($subscriptions), 200);
       
    }

}
