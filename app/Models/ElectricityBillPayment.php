<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectricityBillPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'platform',
        'biller',
        'biller_code',
        'billing_product',
        'billing_product_code',
        'customer_identifier',
        'customer_name',
        'amount',
        'charges',
        'transaction_reference',
        'request',
        'response',
        'response2',
        'reference',
        'token',
        'status',
    ];
}
