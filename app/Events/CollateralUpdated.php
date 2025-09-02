<?php

namespace App\Events;

use App\Models\Collateral;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollateralUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $collateral;

    public function __construct(Collateral $collateral)
    {
        $this->collateral = $collateral;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->collateral->user_id);
    }

    public function broadcastAs()
    {
        return 'collateral.updated';
    }
}