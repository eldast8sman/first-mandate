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
        'lease_start',
        'lease_end',
        'rent_term',
        'rent_amount',
        'rent_due_date',
        'rent_payment_status',
        'payment_type',
        'no_of_installments',
        'installment_amount',
        'rent_renewal_status',
        'renew_rent'
    ];
}
