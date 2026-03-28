<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class YouAreApprovedMail extends Mailable 
{
    use Queueable, SerializesModels;

    public $business_name;

    public function __construct(string $business_name)
    {
        $this->business_name = $business_name;
        
    }

    public function build()
    {
        return $this->subject('You are approved')->markdown('emails.approved',['business_name'=>$this->business_name]);
    }
}
