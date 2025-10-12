<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentPaymentInstallment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'rent_payment_id',
        'payment_method',
        'amount',
        'no_of_installment',
        'status'
    ];
}
