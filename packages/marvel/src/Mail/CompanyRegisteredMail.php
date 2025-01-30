<?php

namespace Marvel\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;
    public array $company=[];
    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->company=$data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Company Registered Mail',
        );
    }

    /**
     * Get the message content definition.
     */

    public function build()
    {
        return $this->markdown('emails.company_registered');
    }


}
