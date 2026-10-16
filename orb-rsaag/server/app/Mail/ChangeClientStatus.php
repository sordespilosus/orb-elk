<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeClientStatus extends Mailable
{
    use Queueable, SerializesModels;

    public $clientName;
    public $status;
    public $reasons;

    /**
     * Create a new message instance.
     * @param object $params
     * @return void
     */
    public function __construct($params)
    {
        $this->clientName = $params->clientName;
        $this->status = $params->status;
        $this->reasons = $params->reasons;
        $this->subject = 'Решение по заявке на добавление системы "' . $params->clientName . '"';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.change_client_status');
    }
}
