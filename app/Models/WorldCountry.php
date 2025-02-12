<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorldCountry extends Model
{
    use HasFactory;
    protected $fillable = [
        "countryCode",
        "countryName",
        "currencyCode",
        "population",
        "capital",
        "continentName",
    ];
}
