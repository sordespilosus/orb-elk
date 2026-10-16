<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendChanges extends Mailable
{
    use Queueable, SerializesModels;

    public $clientName;

    /**
     * Create a new message instance.
     * @param object $params
     * @return void
     */
    public function __construct($params)
    {
        $this->clientName = $params->clientName;
        $this->subject = 'Завка на внесение изменений в систему "' . $params->clientName . '" паринята';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.send_changes');
    }
}
