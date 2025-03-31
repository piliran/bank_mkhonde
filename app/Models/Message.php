<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Chat;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['chat_id', 'sender_id', 'message'];

    // A message belongs to a chat
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    // A message is sent by a user
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
