<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pinCode;

    public function __construct($code)
    {
        $this->pinCode = $code;
    }

    public function build()
    {
        return $this->subject('Your Password Reset Code')
            ->view('emails.password_reset_code');
    }
}
