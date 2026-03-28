<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class YouAreRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $business_name;
    public function __construct(string $business_name)
    {
        $this->business_name = $business_name;  
    }

    public function build()
    {
        return $this->subject('You are rejected')->markdown(
            'emails.rejected',
            [
                'business_name'=>$this->business_name,
            ]
            );
    }

   

    

}
