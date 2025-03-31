<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Start a chat or get an existing one
    public function startChat(Request $request)
    {
        $request->validate([
            'lender_id' => 'required|exists:users,id',
            'borrower_id' => 'required|exists:users,id',
        ]);

        // Check if the chat already exists
        $chat = Chat::where('lender_id', $request->lender_id)
                    ->where('borrower_id', $request->borrower_id)
                    ->first();

        if (!$chat) {
            $chat = Chat::create([
                'lender_id' => $request->lender_id,
                'borrower_id' => $request->borrower_id,
            ]);
        }

        return response()->json($chat);
    }

    // Fetch chat messages
    public function getMessages($chatId, Request $request)
    {
        $userId = $request->user()->id;

        $chat = Chat::findOrFail($chatId);

        // Retrieve messages for the chat
        $messages = $chat->messages()->with('sender')->get();

        // Mark all unread messages as read for the user who is not the sender
        Message::where('chat_id', $chatId)
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function readMessages($chatId, Request $request)
    {
        $userId = $request->user()->id;


        // Mark all unread messages as read for the user who is not the sender
      $message= Message::where('chat_id', $chatId)
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->update(['is_read' => true]);

        return response()->json($message);
    }

    public function listChats(Request $request, $user_id)
    {
        
        $chats = Chat::where('lender_id', $user_id)
                    ->orWhere('borrower_id', $user_id)
                    ->with(['lender', 'borrower', 'messages.sender']) // Eager-load lender, borrower, and messages with their sender
                    ->get();
    
        return response()->json($chats);
    }

         public function unReadMessageCount(Request $request)
        {
            $user_id=Auth::user()->id;
            // Retrieve chats for the user (either as lender or borrower), with messages
            $chats = Chat::where('lender_id', $user_id)
                        ->orWhere('borrower_id', $user_id)
                        ->with(['lender', 'borrower', 'messages.sender']) // Eager-load lender, borrower, and messages with their sender
                        ->get();

            // Create an array to store the chats and their unread message count
            $chatsWithUnreadCount = $chats->map(function($chat) use ($user_id) {
                // Calculate unread messages for each chat
                $unreadMessagesCount = $chat->messages()
                    ->where('is_read', false)
                    ->where('sender_id', '!=', $user_id)
                    ->count();

                // Return the chat data along with the unreadMessagesCount as an additional field
                return [
                    'chat' => $chat, // Chat data as it is
                    'unreadMessagesCount' => $unreadMessagesCount // Additional unread messages count
                ];
            });

            // Return the original chats and the unread message count
            return response()->json($chatsWithUnreadCount);
        }

        public function getUserChats()
            {
                $userId=Auth::user()->id;
                return Chat::where('lender_id', $userId)
                            ->orWhere('borrower_id', $userId)
                            ->get();
            }


    }

