<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;


class EmailVerificationMail extends Mailable
{
    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Verify Your Email')
            ->view('emails.verify_email')
            ->with(['otp' => $this->otp]);
    }
}
