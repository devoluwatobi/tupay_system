<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Wallet;
use App\Models\OTPCodes;
use App\Models\BankDetails;
use App\Models\Verification;
use App\Models\FundTransaction;
use App\Models\WalletTransaction;
use Laravel\Passport\HasApiTokens;
use App\Models\UserFundBankAccount;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'photo',
        'dob',
        'role',
        'country',
        'email_verified_at',
        'phone_verified_at',
        'api_token',
        'fcm',
        'referrer'
    ];

    // create constant for user role
    const ROLE_USER = '0';
    const ROLE_ADMIN = '1';
    const ROLE_SUPER_ADMIN = '2';


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function rmb(): HasOne
    {
        return $this->hasOne(RMBWallet::class, 'user_id');
    }


    public function activationCode(): HasOne
    {
        return $this->hasOne(OTPCodes::class, 'user_id');
    }

    public function fundAccount(): HasMany
    {
        return $this->hasMany(UserFundBankAccount::class, 'user_id');
    }
    public function fundTransactions(): HasMany
    {
        return $this->hasMany(FundTransaction::class, 'user_id');
    }
    public function banks(): HasMany
    {
        return $this->hasMany(BankDetails::class, 'user_id');
    }

    public function walletTransaction(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'user_id');
    }
}
