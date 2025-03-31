<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOffline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;
    public $last_seen;

    public function __construct($user_id, $last_seen)
    {
        $this->user_id = $user_id;
        $this->last_seen = $last_seen;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("user-status.{$this->user_id}");
    }

    public function broadcastAs()
    {
        return 'user.offline';
    }
}
