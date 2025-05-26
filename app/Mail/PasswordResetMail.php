<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    public function __construct($token, $email)
    {
        $this->url = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . $email;
    }

    public function build()
    {
        return $this->subject('Réinitialisation de mot de passe')
                    ->view('emails.password_reset')
                    ->with(['url' => $this->url]);
    }
}
