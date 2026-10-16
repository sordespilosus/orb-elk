<?php

namespace App\Jobs;

use App\Mail\NewsletterUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{DB, Mail};

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $emails;
    public $subject;
    public $description;
    public $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emails, $subject, $description, $params)
    {
        $this->emails = $emails;
        $this->subject = $subject;
        $this->description = $description;
        $this->params = $params;
    }

    /**
     * Раскрывает чанк, то есть создаёт из чанка почт задачи отправки письма на каждую почту
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->emails as $email) {
            // Находим первую почту из mailer.user_mails со статусом wait - ожидание
            $raw = DB::table('mailer.user_mails')
                ->whereJsonContains('params', [
                    'unique_code' => $this->params['unique_code'],
                    'chunk_id' => $this->params['chunk_id']
                ])
                ->whereIn('email', $this->emails)
                ->where('email', $email)
                ->where('status', 'wait');

            // Если такая запись есть - отправляем письмо на почту и меняем статус получателя на send
            if ($raw->exists()) {
                $mail = (new NewsletterUser($email, $this->subject, $this->description))->onQueue('email');
                Mail::to($email)->queue($mail);
                $user_mail = $raw->first();
                $raw->where('id', $user_mail->id)->update(['status' => 'send']);
                sleep(1);
            }
        }
    }
}
