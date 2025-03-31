<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\LoanRequestApproved;
use App\Events\NewNotificationEvent;
use App\Events\MessageSent;




class MessageController extends Controller
{
    // Send a message
    public function sendMessage(Request $request)
    {
        // Create the new message
        $message = Message::create([
            'chat_id' => $request->chat_id,
            'sender_id' => $request->sender_id,
            'message' => $request->message,
        ]);
    
        // Retrieve the chat to determine the receiver
        $chat = Chat::findOrFail($request->chat_id);
    
        // Determine the receiver based on the sender
        $receiverId = ($chat->lender_id == intVal($request->sender_id)) ? $chat->borrower_id : $chat->lender_id;
    
        // Retrieve the newly created message with the sender's details
        $messageWithSender = Message::where('id', $message->id)
            ->with('sender') // Include the sender relationship
            ->first();
    
        $receiver = User::find($receiverId);
    
        // Trigger the WebSocket event for real-time communication
        broadcast(new MessageSent($messageWithSender, $receiver))->toOthers();
    
        if ($receiver && $receiver->expo_push_token) {
            // Prepare the push notification title based on the sender's role
            $notificationTitle = "New message from " .
                (($chat->lender_id == $request->sender_id)
                    ? ($messageWithSender->sender->company_name ?: 'Unknown Sender')
                    : ("{$messageWithSender->sender->first_name} {$messageWithSender->sender->last_name}"));
    
            // Send push notification logic here
            $this->sendPushNotification(
                $receiver->expo_push_token,
                $notificationTitle,
                $message->message
            );
        } else {
            Log::warning('Receiver not found or no expo_push_token', ['receiver' => $receiver]);
        }
    
        // Return the message with sender details in JSON format
        return response()->json($messageWithSender);
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
                \Log::error('Error sending push notification', [
                    'error' => $response->body(), // Log the body of the error response
                ]);
            } else {
                // \Log::info('Push notification sent successfully', [
                //     'response' => $response->json(),
                // ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send push notification', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
    
    
}
