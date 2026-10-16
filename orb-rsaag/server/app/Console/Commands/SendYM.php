<?php

namespace App\Console\Commands;

use GuzzleHttp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendYM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send_ym';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command send file with YM to Yandex and archive them';

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
        $filePath = storage_path('ym') . '/*.csv';

        $archivePath = storage_path('ym/archive');

        if (!file_exists($archivePath)) {
            if (!mkdir($archivePath, 0777, true) && !is_dir($archivePath)) {
                Log::error('Cant create a directory', ['path' => $archivePath]);
                return null;
            }
        }

        $files = glob($filePath);
        if (!$files || !count($files)) {
            $this->info('All files have already sent');
            return 0;
        }

        $counter = env('YA_COUNTER', null);            // Укажите номер счетчика
        $token = env('YA_TOKEN', null);              // Укажите OAuth-токен
        $client_id_type = 'USER_ID';
        if (!$counter || !$token) {
            $this->info('Counter and token are necessary');
            return 0;
        }
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        ));//Чтобы не валилось с ошибкой проверки серитфиката
        $optionsRequest = [
            'headers' => [
                'Authorization' => 'OAuth ' . $token
            ]
        ];
        foreach ($files as $file) {
            $fileParts = explode('/', $file);
            $openedFile = fopen($file, 'r');

            $optionsRequest['multipart'] = [
                [
                    'name' => 'file',
                    'contents' => $openedFile
                ],
            ];

            try {
                $response = $http->post("https://api-metrika.yandex.ru/management/v1/counter/$counter/offline_conversions/upload?client_id_type=$client_id_type", $optionsRequest);
                $content = json_decode($response->getBody(), true);
                if (!($content && is_array($content) && isset($content['uploading']['status']) && $content['uploading']['status'] === 'UPLOADED')) {
                    throw new \Exception('Sending file was not successful');
                }

                $nameParts = explode('.', $fileParts[count($fileParts) - 1]);
                $newFileName = reset($nameParts) . (file_exists(reset($nameParts) . '.' . end($nameParts)) ? '_' . time() : '') . '.' . end($nameParts);
                rename($file, "$archivePath/$newFileName");
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), ['file' => $file]);
                $this->error($exception->getMessage());
            }
        }

        Log::info('Метрика загружена');
        return 0;
    }
}
