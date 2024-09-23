<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id', 'lender_id', 'amount', 'repayment_amount', 
        'repayment_period', 'interest_rate', 'collateral_id', 'status', 'created_at'
    ];

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function lender()
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    public function collateral()
    {
        return $this->belongsTo(Collateral::class);
    }
}
