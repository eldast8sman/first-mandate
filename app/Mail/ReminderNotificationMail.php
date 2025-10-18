<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Reminder;
use App\Models\User;

class ReminderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $reminder;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Reminder $reminder, User $user)
    {
        $this->reminder = $reminder;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: ' . ($this->reminder->short_description ?: $this->reminder->reminder_type),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder_notification',
            with: [
                'user_name' => $this->user->name,
                'reminder_type' => $this->reminder->reminder_type,
                'short_description' => $this->reminder->short_description,
                'amount' => $this->reminder->amount,
                'money_reminder' => $this->reminder->money_reminder,
                'reminder_date' => $this->reminder->next_reminder_date,
                'reminder_time' => $this->reminder->reminder_time,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
