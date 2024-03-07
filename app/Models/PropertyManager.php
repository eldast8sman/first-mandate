<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'landlord_id',
        'manager_id',
        'name',
        'email',
        'status'
    ];
}
