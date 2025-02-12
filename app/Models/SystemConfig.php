<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'updated_by',
    ];
}
