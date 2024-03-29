<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'due_date_id',
        'user_id',
        'user_type',
        'recipient_type',
        'recipient_id',
        'reminder_type',
        'short_description',
        'frequency_type',
        'recurring_type',
        'next_reminder_date',
        'reminder_time',
        'recurring_limit',
        'total_sent',
        'receiving_medium',
        'money_reminder',
        'amount',
        'status'
    ];
}
