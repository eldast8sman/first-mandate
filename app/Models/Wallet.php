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
        'bvn',
        'tx_ref',
        'balance',
        'total_credit',
        'total_debit_amount',
        'bank',
        'bank_code',
        'account_number',
        'account_name',
        'rec_bank',
        'rec_account_number',
        'rec_account_name'
    ];
}
