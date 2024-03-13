<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DueDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'landlord_id',
        'property_tenant_id',
        'property_id',
        'property_unit_id',
        'property_manager_id',
        'due_date',
        'purpose',
        'remarks',
        'cash_payment',
        'status'
    ];
}
