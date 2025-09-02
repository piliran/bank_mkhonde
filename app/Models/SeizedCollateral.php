<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeizedCollateral extends Model
{
    use HasFactory;

    protected $fillable = [
        'lender_id',
        'collateral_id',
        'seized_at',
        'reason',
        'status',
        'recovery_amount',
        'disposed_at'
    ];

    protected $casts = [
        'seized_at' => 'datetime',
        'disposed_at' => 'datetime',
        'recovery_amount' => 'decimal:2',
    ];

    // Relationship to lender
    public function lender()
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    // Relationship to collateral
    public function collateral()
    {
        return $this->belongsTo(Collateral::class);
    }

    // Relationship to borrower through collateral
    public function borrower()
    {
        return $this->hasOneThrough(
            User::class,
            Collateral::class,
            'id', // Foreign key on collaterals table
            'id', // Foreign key on users table
            'collateral_id', // Local key on seized_collaterals table
            'user_id' // Local key on collaterals table
        );
    }
}