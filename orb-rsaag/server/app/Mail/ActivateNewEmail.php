<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivateNewEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $link;

    /**
     * Create a new message instance.
     * @param string $token
     * @param string $email
     * @return void
     */
    public function __construct($params)
    {
        $this->link = route('activate_new_email') . '?' . http_build_query(['activation_key' => $params->key, 'email' => $params->email]);
        $this->subject = 'Смена адреса электронной почты в ГИС ЕЛК ОО';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.activate_new_email');
    }
}
