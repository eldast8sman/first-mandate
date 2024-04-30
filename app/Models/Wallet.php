<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'balance',
        'total_credit',
        'total_debit_amount',
        'bank',
        'bank_code',
        'account_number',
        'account_name'
    ];
}
