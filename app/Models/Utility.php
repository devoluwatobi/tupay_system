<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Utility extends Model
{

    use HasFactory;
    protected $fillable = [
        'name',
        'service_id',
        'image',
        'prefix',
        'status',
    ];

    // networks constants
    const NETWORK_MTN = 'mtn';
    const NETWORK_GLO = 'glo';
    const NETWORK_AIRTEL = 'airtel';
    const NETWORK_9MOBILE = 'etisalat';

    // cable constants
    const CABLE_DSTV = 'dstv';
    const CABLE_GOTV = 'gotv';
    const CABLE_STARTIMES = 'startimes';
    const CABLE_SHOWMAX = 'showmax';

    // cable constants
    const POWER_IKEJA = 'ikeja-electric';
    const POWER_EKO = 'eko-electric';
    const POWER_KANO = 'kano-electric';
    const POWER_PH = 'portharcourt-electric';
    const POWER_JOS = 'jos-electric';
    const POWER_IBADAN = 'ibadan-electric';
    const POWER_KADUNA = 'kaduna-electric';
    const POWER_ABUJA = 'abuja-electric';

    // power types
    const POWER_PREPAID = 'prepaid';
    const POWER_POSTPAID = 'postpaid';


    // bills constants
    const POWER = 'Electricity Bill';
    const TV = 'Tv Bills';
}
