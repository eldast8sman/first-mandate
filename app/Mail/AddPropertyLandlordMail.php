<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AddPropertyLandlordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $manager;
    public $new_user;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $manager, $new_user, $token="")
    {
        $this->name = $name;
        $this->manager = $manager;
        $this->token = $token;
        $this->new_user = $new_user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Added as Landlord',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.add_property_landlord_mail',
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
