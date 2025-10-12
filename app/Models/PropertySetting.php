<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'user_type',
        'tenant_pays_commission',
        'pay_rent_to'
    ];
}
