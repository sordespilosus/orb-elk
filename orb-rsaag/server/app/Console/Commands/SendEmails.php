<?php

namespace App\Console\Commands;

use App\Extensions\Helpers;
use App\Mail\{
    ActivateNewEmail,
    ChangeChangesStatus,
    ChangeClientStatus,
    RegistrationMail,
    ResetPassword,
    SendChanges,
    SendClient,
    SuccessRegistrationMail,
    WarningChangeEmailToOldEmail
};
use App\UserMail;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Mail};

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send_emails {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sending emails waiting in the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->option('id');

        if ($id) {
            $mails = [UserMail::find($id)];
        } else {
            $mails = UserMail::whereIn('status', ['wait', 'resend'])->cursor();
        }

        foreach ($mails as $mail) {
            $mail->params = json_decode($mail->params);
            if (strripos($mail->email, '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') !== false) {
                continue;
            }
            try {
                $email = null;
                switch ($mail->type) {
                    case 'register':
                    {
                        $email = new RegistrationMail($mail->params->link, $mail->params->code);
                        break;
                    }
                    case 'success_register':
                    {
                        $email = new SuccessRegistrationMail($mail->params->clientName, $mail->params->clientDescription, $mail->params->userLogin, $mail->params->userPassword, $mail->params->esiaLogin);
                        break;
                    }
                    case 'reset_password':
                    {
                        $email = new ResetPassword($mail->params->token, $mail->email);
                        break;
                    }
                    case 'activateNewEmail':
                    {
                        $email = new ActivateNewEmail($mail->params);
                        break;
                    }
                    case 'warningChangeEmailToOldEmail':
                    {
                        $email = new WarningChangeEmailToOldEmail($mail->params);
                        break;
                    }
                    case 'sendClient':
                    {
                        $email = new SendClient($mail->params);
                        break;
                    }
                    case 'sendChanges':
                    {
                        $email = new SendChanges($mail->params);
                        break;
                    }
                    case 'changeChangesStatus':
                    {
                        $email = new ChangeChangesStatus($mail->params);
                        break;
                    }
                    case 'changeClientStatus':
                    {
                        $email = new ChangeClientStatus($mail->params);
                        break;
                    }
                }
                if ($email) {
                    Mail::to($mail->email)->send($email);
                    DB::table('mailer.user_mails')
                        ->where('id', $mail->id)
                        ->update([
                            'updated_at' => date('Y-m-d H:i:s'),
                            'status' => 'send',
                            'attempts_count' => $mail->attempts_count + 1
                        ]);
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                DB::table('mailer.user_mails')->where('id', $mail->id)
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status' => $mail->attempts_count > env('COUNT_ATTEMPTS_MAIL', 5) ? 'error' : 'resend',
                        'attempts_count' => $mail->attempts_count + 1,
                        'error' => $errorMessage
                    ]);
                if ($mail->attempts_count > env('COUNT_ATTEMPTS_MAIL', 5)) {
                    $text = 'Ошибка отправки сообщения пользователю ' . $mail->email . '. Контур: ' . config('app.env');
                    $user = json_encode([
                        'date' => date('d.m.Y H:i:s'),
                        'system' => 'RSAAG',
                        'func' => 'send_emails',
                        'email' => $mail->email,
                    ]);
                    Helpers::noticeError($text, $user, 'SEND_MESSAGE', 'ALERT');
                }
            }
        }
        return 0;
    }
}
