<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("user-status.{$this->user_id}");
    }

    public function broadcastAs()
    {
        return 'user.online';
    }
}
