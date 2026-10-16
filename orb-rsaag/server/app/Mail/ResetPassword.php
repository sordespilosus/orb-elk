<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $action_text;
    public $uEmail;
    public $link;

    /**
     * Create a new message instance.
     * @param string $token
     * @param string $email
     * @return void
     */
    public function __construct($token, $email)
    {
        $this->subject = 'Восстановление пароля в ГИС ЕЛК ОО';
        $this->token = $token;
        $this->uEmail = urlencode($email);
        $this->link = url(config('app.url') . route('password.reset', ['token' => $token, 'email' => $this->uEmail], false));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.reset_password');
    }
}
