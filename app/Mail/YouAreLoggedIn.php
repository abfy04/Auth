<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class YouAreLoggedIn extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $time;
    public $location;
    public $user_agent ;
    public function __construct($time,$location,$user_agent)
    {
        $this->time=$time;
        $this->location=$location;
        $this->user_agent=$user_agent;
    }

     public function build()
    {
        return $this->subject('LogIn')->markdown('emails.login',[
            'time'=>$this->time,
            'location'=>$this->location,
            'user_agent'=>$this->user_agent,
        ]);
    }

}
