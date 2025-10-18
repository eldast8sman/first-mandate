<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendReminderEmailJob;

class CronController extends Controller
{
    /**
     * Queue reminder emails for reminders closest to current time
     * This method runs every 6 hours
     */
    public function sendReminderEmails()
    {
        try {
            $now = Carbon::now('Africa/Lagos');
            $today = $now->toDateString();
            
            // Get all reminders for today that haven't been sent
            $todaysReminders = Reminder::where('next_reminder_date', $today)
                ->whereColumn('total_sent', '<', 'recurring_limit');

            if ($todaysReminders->count() < 1) {
                Log::info("No reminders found for today: {$today}");
                return response()->json([
                    'success' => true,
                    'message' => 'No reminders for today',
                    'queued_count' => 0
                ]);
            }

            $remindersToSend = $todaysReminders->get();

            $queuedCount = 0;

            foreach ($remindersToSend as $reminder) {
                $user = User::find($reminder->recipient_id);
                
                if ($user && $user->email) {
                    // Queue the email job instead of sending directly
                    SendReminderEmailJob::dispatch($reminder->id, $user->id);
                    $queuedCount++;
                    
                    Log::info("Reminder email queued for {$user->email} for reminder ID: {$reminder->id}");
                }
            }

            Log::info("Reminder cron job completed. Queued {$queuedCount} reminder emails for {$today}.");
            
            return response()->json([
                'success' => true,
                'message' => "Queued {$queuedCount} reminder emails",
                'queued_count' => $queuedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Reminder cron job failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Reminder cron job failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
