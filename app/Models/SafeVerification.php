<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SafeVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'safe_id',
        'type',
        'status',
        "value",
        "otp_id",
        'firstName',
        'middleName',
        'lastName',
        'dateOfBirth',
        'phoneNumber1',
        'phoneNumber2',
        'gender',
        'enrollmentBank',
        'enrollmentBranch',
        'email',
        'lgaOfOrigin',
        'lgaOfResidence',
        'maritalStatus',
        'nationality',
        'residentialAddress',
        'stateOfOrigin',
        'stateOfResidence',
        'title',
        'watchListed',
        'levelOfAccount',
        'registrationDate',
        'imageBase64',
        "validation_data",
        "request_data",
        "otp",

    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id',);
    }
}
