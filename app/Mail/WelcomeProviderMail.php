<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;


class WelcomeProviderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $account;
    public $provider;

    /**
     * Create a new message instance.
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->provider = $account->provider; // assumes one-to-one
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Welcome to Our Platform!')
                    ->markdown('emails.welcome_provider');
    }
}