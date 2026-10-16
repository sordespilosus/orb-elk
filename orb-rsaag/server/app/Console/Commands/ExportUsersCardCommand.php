<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExportUsersCardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:export-with-card {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $fileName = 'export_users_with_card';

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
     * @throws
     */
    public function handle()
    {
        $headers = [
            'card_id' => 'Номер карты',
            'user_id' => 'ID Пользователя',
            'fio' => 'user_fio',
            'email' => 'email',
        ];

        // Создаем имя файла, указатель на него и записываем заголовок.
        $datetime = Carbon::now()->format('__d_m_Y_H_i');
        $fileName = $this->fileName . $datetime . '.csv';
        $file = fopen($this->getPath() . '/' . $fileName, 'w');
        fputcsv($file, $headers, "\t");

        DB::table('oauth.ref_user_cards as  ruc')
            ->leftJoin('oauth.users as u', 'u.id', '=', 'ruc.user_id')
            ->orderBy('u.id')
            ->chunk(500, function ($cards) use ($headers, $file) {

                // Бежим по записям и вносим в файл.
                foreach ($cards as $card) {
                    $item = [];
                    foreach ($headers as $key => $name) {
                        $item[$name] = $card->$key ?? null;
                    }

                    fputcsv($file, $item, "\t");
                }
            });

        fclose($file);
    }

    /**
     * @return string
     * @throws FileNotFoundException
     */
    protected function getPath(): string
    {
        /*
         * Смотрим аргумент
         * Смотрим env параметр
         * Проверяем существование пути
         * Иначе просто сохраняем в storage
         */

        $path = env('EXPORT_WITH_CARD_PATH', null);
        if (!$path) {
            $path = $this->argument('path');
        }

        if ($path && !file_exists($path)) {
            throw new FileNotFoundException('Not found path!');
        } else if (!$path) {
            $path = storage_path('export');
            if (!file_exists($path))
                mkdir($path);
        }

        return $path;
    }
}
