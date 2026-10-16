<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};

class ExportUsersToListmonk extends Command
{
    protected $signature = 'import:users';

    protected $description = 'Экспорт пользователей в формате .csv для импорта в сервис почтовой рассылки';

    protected $batchSize = 10000;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lastImportDate = DB::table('mailer.export')->first()->last_date;

        Log::info("Дата последнего экспорта: $lastImportDate");

        $totalRows = DB::table('oauth.users')
            ->where('created_at', '>', $lastImportDate)
            ->where('email', '~', '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}')
            ->where('registration_method', '=', 'esia')
            ->count();

        if ($totalRows == 0) {
            Log::info("Новых пользователей нет");
            return;
        }

        $operationsCount = intdiv($totalRows, $this->batchSize) + 1;
        $executedRows = 0;

        Log::info("Найдено $totalRows записей. Всего будет выполнено запросов: $operationsCount");

        $csvFile = storage_path('app/public/users_' . date('Y-m-d') . '.csv');

        $fp = fopen($csvFile, 'w');
        fputcsv($fp, ['email', 'name', 'attributes']);

        for ($i = 0; $i < $operationsCount; $i++) {
            $logI = $i + 1;
            Log::info("Запрос № $logI");

            $users = DB::table('oauth.users')
                ->leftJoin('dicts.d_areas', 'oauth.users.city', '=', 'dicts.d_areas.area_id')
                ->where('created_at', '>', $lastImportDate)
                ->where('email', '~', '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}')
                ->where('registration_method', '=', 'esia')
                ->orderBy('id')
                ->limit($this->batchSize)
                ->offset($this->batchSize * $i)
                ->get();

            foreach ($users as $user) {
                $gender = $user->gender;

                if ($gender != null) {
                    $gender = $gender == 'male' ? 'М' : 'Ж';
                } else {
                    $gender = 'Не заполнено';
                }

                $attribs = json_encode([
                    'elk_id' => $user->id,
                    'gender' => $gender,
                    'area_name' => $user->area_name ?? 'Не заполнено',
                    'birthday' => $user->birthday ?? 'Не заполнено'
                ]);
                fputcsv($fp, [$user->email, $user->fio, $attribs]);
            }

            $rowCount = $users->count();
            $executedRows += $rowCount;

            Log::info("Запрос № $logI выполнен. Всего записано $executedRows из $totalRows");

        }

        fclose($fp);
        Log::info("Запись в файл $csvFile завершена.");

        $endpoint = config('listmonk.service_url') . '/api/import/subscribers';
        $auth = json_decode(config('listmonk.service_auth'));
        $lists = json_decode(config('listmonk.service_subscribers_lists'));

        try {
            //Чтобы не валилось с ошибкой проверки серитфиката
            $http = new Client(['verify' => false]);

            $response = $http->post($endpoint, [
                'auth' => $auth,
                'multipart' => [
                    [
                        'name' => 'params',
                        'contents' => json_encode([
                            'mode' => 'subscribe',
                            'delim' => ',',
                            'lists' => $lists,
                            'overwrite' => false,
                        ]),
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($csvFile, 'r'),
                    ]
                ],
            ]);

            Log::info("Загружено $executedRows пользователей");
            DB::table('mailer.export')->update(['last_date' => "now()"]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->error($e->getMessage());
        }
    }
}
