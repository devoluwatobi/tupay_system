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
use Illuminate\Support\Facades\Hash;
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
        'referrer',
        'pin',
        'gender',
        'status',
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

    public function setPinAttribute($value)
    {
        $this->attributes['pin'] = Hash::make($value);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function rewardWallet(): HasOne
    {
        return $this->hasOne(RewardWallet::class, 'user_id');
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

    public function rewardTransaction(): HasMany
    {
        return $this->hasMany(RewardWalletTransaction::class, 'user_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(SafeVerification::class, 'user_id');
    }

    public function name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getMaskedEmailAttribute()
    {
        return $this->maskEmail($this->email);
    }

    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email; // Invalid email case

        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = strlen($username) > 2
            ? substr($username, 0, 2) . str_repeat('*', strlen($username) - 2)
            : $username . '**';

        $domainParts = explode('.', $domain);
        $maskedDomain = str_repeat('*', strlen($domainParts[0])) . '.' . implode('.', array_slice($domainParts, 1));

        return "{$maskedUsername}@{$maskedDomain}";
    }
}
