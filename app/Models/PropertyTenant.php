<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyTenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'landlord_id',
        'property_id',
        'property_unit_id',
        'name',
        'email',
        'phone',
        'current_tenant',
        'lease_state',
        'lease_end'
    ];
}
