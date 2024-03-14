<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AddTenantMail extends Mailable
{
    use Queueable, SerializesModels;

    public $landlord;
    public $token;
    public $name;
    public $new_user;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $landlord, $new_user=false, $token="")
    {
        $this->name = $name;
        $this->landlord = $landlord;
        $this->new_user = $new_user;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been added as a Tenant',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.add_tenant_mail',
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
