<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Notice;
use App\Models\User;

class NoticeReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $notice;
    protected $recipient;
    protected $sender;

    /**
     * Create a new message instance.
     */
    public function __construct(Notice $notice, User $recipient, User $sender)
    {
        $this->notice = $notice;
        $this->recipient = $recipient;
        $this->sender = $sender;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notice Reminder - Acknowledgment Required',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notice_reminder',
            with: [
                'recipient_name' => $this->recipient->name,
                'sender_name' => $this->sender->name,
                'sender_type' => $this->notice->sender_type,
                'notice_type' => $this->notice->type,
                'notice_description' => $this->notice->description,
                'notice_date' => $this->notice->notice_date,
                'notice_time' => $this->notice->notice_time,
                'notice_uuid' => $this->notice->uuid,
                'created_at' => $this->notice->created_at->format('F j, Y \a\t g:i A'),
            ]
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
