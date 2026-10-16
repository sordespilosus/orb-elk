<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $action_text;
    public $link;
    public $registrationCode;

    /**
     * Create a new message instance.
     * @param string $link
     * @param string $registrationCode
     * @return void
     */
    public function __construct($link, $registrationCode)
    {
        $this->link = $link;
        $this->subject = 'Подтверждение регистрации в ГИС ЕЛК ОО';
        $this->registrationCode = $registrationCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail/confirm_register_email');
    }
}
