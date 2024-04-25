<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'user_type_id',
        'type',
        'trans_reference',
        'transaction_id',
        'amount',
        'platform',
        'request',
        'response1',
        'response2',
        'response3',
        'status',
        'event',
        'event_id',
        'value_given'
    ];
}
