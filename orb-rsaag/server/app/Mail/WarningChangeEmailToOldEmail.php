<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WarningChangeEmailToOldEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $newEmail;

    /**
     * Create a new message instance.
     * @param string $token
     * @param string $email
     * @return void
     */
    public function __construct($params)
    {
        $this->newEmail = $params->email;
        $this->subject = 'Предупреждение о смене адреса электронной почты в ГИС ЕЛК ОО';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.warning_to_old_email');
    }
}
