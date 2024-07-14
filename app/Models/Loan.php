<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = ['lender_id', 'borrower_id', 'amount', 'interest_rate', 'borrowed_at', 'due_at', 'returned_at', 'repay_amount', 'status'];

    public function lender()
    {
        return $this->belongsTo(Lender::class);
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
