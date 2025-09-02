<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id', 'lender_id', 'amount', 'repayment_amount', 
        'interest_rate', 'repayment_period', 'repayment_due_date', 
        'collateral_id', 'status', 'date_granted', 'actual_amount_loaned',
        'transaction_id'
    ];

    // Add new statuses
    const STATUS_PENDING_DISBURSEMENT = 'pending_disbursement';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_DEFAULTED = 'defaulted';

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

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function seizedCollateral()
    {
        return $this->hasOne(SeizedCollateral::class, 'collateral_id', 'collateral_id')
                    ->where('status', 'held');
    }

    // Method to seize collateral
    public function seizeCollateral($reason = null)
    {
        if (!$this->collateral_id) {
            return false;
        }

        return SeizedCollateral::create([
            'lender_id' => $this->lender_id,
            'collateral_id' => $this->collateral_id,
            'reason' => $reason ?? 'Loan default',
            'seized_at' => now(),
        ]);
    }
}