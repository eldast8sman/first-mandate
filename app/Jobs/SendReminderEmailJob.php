<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Reminder;
use App\Models\User;
use App\Mail\ReminderNotificationMail;
use Carbon\Carbon;

class SendReminderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reminder;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct($reminder_id, $user_id)
    {
        $this->reminder = Reminder::find($reminder_id);
        $this->user = User::find($user_id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send the reminder email
            Mail::to($this->user->email)->send(new ReminderNotificationMail($this->reminder, $this->user));

            // Update reminder status and count
            $this->reminder->update([
                'total_sent' => $this->reminder->total_sent + 1
            ]);

            Log::info("Reminder email queued and sent successfully to {$this->user->email} for reminder ID: {$this->reminder->id}");

            if(($this->reminder->total_sent < $this->reminder->recurring_limit) or ($this->reminder->recurring_limit == 0)){
                // Logic to set next_reminder_date based on frequency_type can be added here
                switch($this->reminder->recurring_type){
                    case 'daily':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addDay();
                        break;
                    case 'weekly':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addWeek();
                        break;
                    case 'biweekly':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addWeeks(2);
                        break;
                    case 'monthly':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addMonth();
                        break;
                    case 'quarterly':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addMonths(3);
                        break;
                    case 'biannually':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addMonths(6);
                        break;
                    case 'annually':
                        $next_date = Carbon::parse($this->reminder->next_reminder_date)->addYear();
                        break;
                    default:
                        $next_date = null;
                }

                if($next_date){
                    $this->reminder->update([
                        'next_reminder_date' => $next_date->toDateString()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to send queued reminder email for reminder ID {$this->reminder->id}: " . $e->getMessage());
            throw $e; // Re-throw to trigger job retry if needed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendReminderEmailJob failed for reminder ID {$this->reminder->id}: " . $exception->getMessage());
    }
}
