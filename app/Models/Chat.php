<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Message;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['lender_id', 'borrower_id'];

    // A chat belongs to two users: lender and borrower
    public function lender()
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    // A chat can have many messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
