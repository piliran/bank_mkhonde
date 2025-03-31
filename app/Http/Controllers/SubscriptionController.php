<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use App\Models\Subscription;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Support\Facades\Auth;
use App\Events\NewNotificationEvent;
use Illuminate\Support\Facades\Http;


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

    
        return response()->json(['message' => 'Subscription created successfully'],201);
    }
    

    public function checkSubscription($lenderId)
    {
        $borrower = Auth::user();
        $isSubscribed = Subscription::where('borrower_id', $borrower->id)
            ->where('lender_id', $lenderId)
            ->exists();

        return response()->json(['is_subscribed' => $isSubscribed]);
    }


    // public function subscription()
    // {
    //     $borrower = Auth::user();
       
        
    //     $subscriptions = Subscription::with([
    //                         'borrower.banks', 
    //                         'lender.banks'    
    //                     ])
    //                     ->where(function($query) use ($borrower) {
    //                         $query->where('borrower_id', $borrower->id)
    //                               ->orWhere('lender_id', $borrower->id);
    //                     })
    //                     ->get();


    //     return response()->json(SubscriptionResource::collection($subscriptions), 200);
       
    // }

    public function subscription()
    {
        $borrower = Auth::user();

        // Fetch subscriptions for this borrower
        $subscriptions = Subscription::with([
                                'borrower.banks', 
                                'lender.banks'    
                            ])
                            ->where(function($query) use ($borrower) {
                                $query->where('borrower_id', $borrower->id)
                                    ->orWhere('lender_id', $borrower->id);
                            })
                            ->get();

        // Map through subscriptions and check subscription status for each lender
        $subscriptionData = $subscriptions->map(function ($subscription) use ($borrower) {
            // Determine if the borrower has subscribed to this lender
            $isSubscribed = $subscription->borrower_id === $borrower->id;
            
            // Add subscription status to the response
            return [
                'lender' => $subscription->lender,
                'borrower' => $subscription->borrower,
                'banks' => $subscription->lender->banks,
                'is_subscribed' => $isSubscribed, 
            ];
        });

        return response()->json($subscriptionData, 200);
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
