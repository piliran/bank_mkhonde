<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Notification::where('recipient_id', Auth::id())->get();

        return response()->json($notifications, 200);
    }

    public function getNotifications()
    {
        $notifications = Auth::user()->notifications()->orderBy('created_at', 'desc')->get();
        return response()->json($notifications);
    }

    public function markNotificationAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        
        $notification->is_read = true;
        $notification->save();

        return response()->json(['message' => 'Notification marked as read']);
    }


    public function unreadNotificationsCount()
    {
        $count = Auth::user()->notifications()->where('is_read', false)->count();
        return response()->json(['unread_count' => $count]);
    }

}
