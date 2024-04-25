<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlutterwaveWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook',
        'user_id',
        'event',
        'trans_reference',
        'amount'
    ];
}
