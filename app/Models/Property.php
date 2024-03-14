<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'landlord_id',
        'title',
        'property_type',
        'building_type',
        'address',
        'city',
        'state',
        'country',
        'status'
    ];
}
