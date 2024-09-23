<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanCleared implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $borrowerId;
    public $message;

    /**
     * Create a new event instance.
     *
     * @param int $borrowerId
     * @param string $message
     * @return void
     */
    public function __construct($borrowerId, $message)
    {
        $this->borrowerId = $borrowerId;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->borrowerId);
    }

    /**
     * Get the event name to broadcast.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'loan.cleared';
    }

    /**
     * Data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message
        ];
    }
}
