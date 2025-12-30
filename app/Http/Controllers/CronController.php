<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reminder;
use App\Models\Notice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendReminderEmailJob;
use App\Jobs\SendNoticeReminderEmailJob;

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
                ->where(function($query) {
                    $query->whereColumn('total_sent', '<', 'recurring_limit')
                          ->orWhere('recurring_limit', 0);
                });

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

    /**
     * Queue notice reminder emails for unacknowledged notices
     * This method runs daily to remind users of pending notices
     */
    public function sendNoticeReminderEmails()
    {
        try {
            $now = Carbon::now('Africa/Lagos');
            
            // Get all unacknowledged notices that are at least 24 hours old
            $cutoffDate = $now->subDay()->toDateString();
            
            $unacknowledgedNotices = Notice::where('acknowledged_status', 'pending')
                ->where('status', 1) // Active notices only
                ->where('notice_date', '<=', $cutoffDate)
                ->get();

            if ($unacknowledgedNotices->count() < 1) {
                Log::info("No unacknowledged notices found for reminder");
                return response()->json([
                    'success' => true,
                    'message' => 'No unacknowledged notices to remind',
                    'queued_count' => 0
                ]);
            }

            $queuedCount = 0;

            foreach ($unacknowledgedNotices as $notice) {
                $recipient = User::find($notice->receiver_id);
                
                if ($recipient && $recipient->email) {
                    // Queue the notice reminder email
                    SendNoticeReminderEmailJob::dispatch($notice->id, $recipient->id);
                    $queuedCount++;
                    
                    Log::info("Notice reminder email queued for {$recipient->email} for notice ID: {$notice->id}");
                }
            }

            Log::info("Notice reminder cron job completed. Queued {$queuedCount} notice reminder emails.");
            
            return response()->json([
                'success' => true,
                'message' => "Queued {$queuedCount} notice reminder emails",
                'queued_count' => $queuedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Notice reminder cron job failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Notice reminder cron job failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
