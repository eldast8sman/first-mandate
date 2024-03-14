<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'property_id',
        'landlord_id',
        'unit_name',
        'no_of_bedrooms',
        'occupation_status',
        'annual_rent'
    ];
}
