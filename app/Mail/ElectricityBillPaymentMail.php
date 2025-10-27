<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ElectricityBillPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $biller;
    public $customer_name;
    public $customer_identifier;
    public $amount;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $biller, $customer_name, $customer_identifier, $amount, $token)
    {
        $this->name = $name;
        $this->biller = $biller;
        $this->customer_name = $customer_name;
        $this->customer_identifier = $customer_identifier;
        $this->amount = $amount;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Electricity Bill Payment Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.electricty_token_mail',
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
