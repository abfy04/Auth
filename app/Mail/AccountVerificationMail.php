<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;

class AccountVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $account;
    public $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Account $account, $verificationUrl)
    {
        $this->account = $account;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verify Your Email')
                    ->markdown('emails.verify_email');
    }
}