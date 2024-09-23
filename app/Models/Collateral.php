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
    ];
}
