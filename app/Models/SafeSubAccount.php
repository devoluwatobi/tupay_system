<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SafeSubAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id_safe_id',
        'id_type',
        'id_status',
        'status',
        "id_value",
        "otp_id",
        "id_request_data",
        // 'firstName',
        // 'middleName',
        // 'lastName',
        // 'dateOfBirth',
        // 'phoneNumber1',
        // 'phoneNumber2',
        // 'gender',
        // 'enrollmentBank',
        // 'enrollmentBranch',
        // 'email',
        // 'lgaOfOrigin',
        // 'lgaOfResidence',
        // 'maritalStatus',
        // 'nationality',
        // 'residentialAddress',
        // 'stateOfOrigin',
        // 'stateOfResidence',
        // 'title',
        // 'watchListed',
        // 'levelOfAccount',
        // 'registrationDate',
        // 'imageBase64',
        // "validation_data",


    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id',);
    }
}
