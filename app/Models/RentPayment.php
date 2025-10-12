<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenancy_id',
        'rent_amount',
        'payment_type',
        'no_of_installments',
        'installment_amount',
        'installments_paid',
        'next_due_date',
        'rent_start_date',
        'rent_end_date',
        'payment_status'
    ];
}
