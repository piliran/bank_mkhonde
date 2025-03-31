<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel; // Import PresenceChannel
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiver;

    public function __construct(Message $message, User $receiver)
    {
        $this->message = $message;
        $this->receiver = $receiver;
    }

    public function broadcastOn(): array // Change the return type to array
    {
        // Broadcast on both the private and presence channels
        return [
            new PrivateChannel('chat.' . $this->message->chat_id),
            new PresenceChannel('presence.' . $this->message->chat_id), // Add the presence channel
        ];
    }
    
    public function broadcastAs()
    {
        return 'message.sent';
    }
}
