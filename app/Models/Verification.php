<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        "value",
        'name',
        'verification_status',
        "reference",
        "status",
        "dob",
        "data",
        "updates",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
