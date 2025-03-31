<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class LoanRequestWithdrawn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lenderId;
    public $message;
    

    public function __construct($lenderId, $message)
    {
        $this->lenderId = $lenderId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // \Log::info('Broadcasting on channel: user.' . $this->lenderId);

        // return new PrivateChannel("user.{$this->lenderId}");
       
        return [
            new Channel('user.' . $this->lenderId),
        ];
    }

    public function broadcastAs()
    {
        return 'loan.request.withdrawn';
    }
}
