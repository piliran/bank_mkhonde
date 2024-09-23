<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\HasProfilePhoto;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasProfilePhoto;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'user_name',
        'phone',
        'email',
        'password',
        'national_id',
        'traditional_authority',
        'home_village',
        'occupation',
        'airtel_money_number',
        'mpamba_number',
        'home_physical_address',
        'physical_address',
        'current_physical_address',
        'guardian',
        'bank_name',
        'account_number',
        'branch',
        'monthly_income',
        'company_name',
        'lending_limit',
        'lending_minimum',
        'collateral_required',
        'interest_rate',
        'terms_and_conditions',
        'preferred_borrower_criteria',
        'company_annual_revenue',
        'business_registration_number',
        'expo_push_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

      /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];
    

    public function accountType()
    {
        return $this->hasOne(AccountType::class);
    }

    public function loanRequests()
    {
        return $this->hasMany(LoanRequest::class, 'borrower_id');
    }

    public function loansGranted()
    {
        return $this->hasMany(Loan::class, 'lender_id');
    }

    public function collaterals()
    {
        return $this->hasMany(Collateral::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'borrower_id');
    }

    public function banks()
    {
        return $this->hasMany(Bank::class, 'user_id');
    }
}
