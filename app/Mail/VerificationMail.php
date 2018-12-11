<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $recipient;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($recipient, $token)
    {
        //
        $this->recipient = $recipient;
        $this->url = url('/verify/?token=' . $token);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('verification@senseus.ge')
                    ->view('mail.verify');
    }
}
