<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterUser extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $subject;
    public $description;
    public $link;
    private $key = 'd6c806f694d9e1647350de0565ef684d3d5804f2';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $subject, $description, $link = null)
    {
        $this->email = urlencode($email);
        $this->subject = $subject;
        $this->description = $description;
        $this->hash = sha1($this->email . $this->key);
        $this->link = url(config('app.url') . route('mailing.unsubscribe_form', ['token' => $this->hash, 'email' => $this->email], false));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.NewsletterUser', ['data' => $this->description, 'link' => $this->link])
            ->subject($this->subject);
    }
}
