<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $business_name;
    public function __construct($business_name)
    {
        $this->business_name = $business_name;
    }

   


    public function build()
    {
        return $this->subject('You are Approved')->markdown('provider.approved');
    }
}
