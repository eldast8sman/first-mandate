<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagerSendTenantNotice extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $manager;
    public $notice;
    public $link;


    /**
     * Create a new message instance.
     */
    public function __construct($name, $manager, $notice)
    {
        $this->name = $name;
        $this->manager = $manager;
        $this->notice = $notice;
        $this->link = env('FRONTEND_URL').'/tenants/notices';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A Notice has just been sent to you',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.manager_send_tenant_notice',
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
