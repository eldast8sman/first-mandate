<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'sender_type',
        'sender_id',
        'receiver_type',
        'receiver_id',
        'tenant_id',
        'type',
        'description',
        'notice_date',
        'notice_time',
        'acknowledged_status',
        'remarks',
        'status'
    ];
}
