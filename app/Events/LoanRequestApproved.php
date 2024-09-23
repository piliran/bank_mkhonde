<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanRequestApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $borrowerId;
    public $message;

    public function __construct($borrowerId, $message)
    {
        $this->borrowerId = $borrowerId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("user.{$this->borrowerId}");
    }

    public function broadcastAs()
    {
        return 'loan.request.approved';
    }
}
