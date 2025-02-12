<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteAccountRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
