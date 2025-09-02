<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'lender_id',
        'borrower_id',
        'amount',
        'transaction_fee',
        'net_amount',
        'type',
        'status',
        'payment_method',
        'payment_reference',
        'description',
        'metadata',
        'processed_at',
        'completed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function lender()
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }
}