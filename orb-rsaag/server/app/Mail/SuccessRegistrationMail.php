<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuccessRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userLogin;
    public $userPassword;
    public $clientDescription;
    public $clientName;
    public $esiaLogin;

    /**
     * Create a new message instance.
     * @param string $link
     * @param string $registrationCode
     * @return void
     */
    public function __construct($clientName = null, $clientDescription = null, $userLogin = null, $userPassword = null, $esiaLogin = null)
    {
        $this->subject = 'Благодарим за регистрацию в ГИС ЕЛК';
        $this->userLogin = $userLogin;
        $this->userPassword = $userPassword;
        $this->clientDescription = $clientDescription;
        $this->clientName = $clientName;
        $this->esiaLogin = $esiaLogin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail/register_email');
    }
}
