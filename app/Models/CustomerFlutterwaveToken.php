<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFlutterwaveToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_digits',
        'last_digits',
        'card_issuer',
        'card_type',
        'card_expiry',
        'token',
        'country',
        'email',
        'token_expiry'
    ];
}
