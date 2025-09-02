<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collateral extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'value',
        'collateral_file',
        'status',
    ];

    // Relationship to user (owner/borrower)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to loans
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    // Relationship to seized collateral records
    public function seizedRecords()
    {
        return $this->hasMany(SeizedCollateral::class);
    }

    // Check if collateral is currently seized
    public function getIsSeizedAttribute()
    {
        return $this->seizedRecords()->where('status', 'held')->exists();
    }
}