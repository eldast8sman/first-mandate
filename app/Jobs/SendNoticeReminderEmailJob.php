<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Notice;
use App\Models\User;
use App\Mail\NoticeReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNoticeReminderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $noticeId;
    protected $recipientId;

    /**
     * Create a new job instance.
     */
    public function __construct($noticeId, $recipientId)
    {
        $this->noticeId = $noticeId;
        $this->recipientId = $recipientId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $notice = Notice::find($this->noticeId);
            $recipient = User::find($this->recipientId);
            
            if (!$notice || !$recipient) {
                Log::error("Notice reminder job failed: Notice or recipient not found. Notice ID: {$this->noticeId}, Recipient ID: {$this->recipientId}");
                return;
            }

            // Get sender information
            $sender = User::find($notice->sender_id);
            
            if (!$sender) {
                Log::error("Notice reminder job failed: Sender not found. Sender ID: {$notice->sender_id}");
                return;
            }

            // Send the reminder email
            Mail::to($recipient->email)->send(new NoticeReminderMail($notice, $recipient, $sender));
            
            Log::info("Notice reminder email sent successfully to {$recipient->email} for notice ID: {$notice->id}");
            
        } catch (\Exception $e) {
            Log::error("Notice reminder email job failed: " . $e->getMessage());
            throw $e;
        }
    }
}
