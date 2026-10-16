<?php


namespace App\Http\Traits;

use GuzzleHttp;
use Illuminate\Support\Facades\Log;

trait OuterStorageTrait
{
    protected $token = null;

    protected function getToken()
    {
        if (!isset($this->token)) {
            $this->refreshToken();
        }
        return $this->token;
    }

    private function refreshToken()
    {
        //Получаем токен пользователя
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        ));//Чтобы не валилось с ошибкой проверки серитфиката
        try {
            $response = $http->post(env('OUTER_STORAGE') . '/token', [
                'form_params' => [
                    'grant_type' => 'password',
                    'username' => env('OUTER_LOGIN'),
                    'password' => env('OUTER_PASSWORD'),
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(env('OUTER_KEY') . ':' . env('OUTER_SECRET'))
                ]
            ]);
            $content = json_decode($response->getBody());

            if (!$content || !isset($content->access_token)) {
                throw new \Exception('The answer is empty', 500);
            }
            $this->token = $content->access_token;
            return true;
        } catch (\Exception $e) {
            Log::error('Error getting Outer Storage token: ' . $e->getMessage());
            return false;
        }
    }

    protected function getOuterData($url, $optionsRequest = [])
    {
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        ));//Чтобы не валилось с ошибкой проверки серитфиката
        if (!isset($optionsRequest['headers']['Authorization'])) {
            $optionsRequest['headers']['Authorization'] = 'Bearer ' . $this->getToken();
        }
        try {
            $response = $http->get($url, $optionsRequest);

            $content = json_decode($response->getBody());

            if (!$content) {
                throw new \Exception('The answer is empty', 500);
            }
            return $content;
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                return 404;
            }
            Log::error('Error getting data from Outer Storage' . $e->getMessage() . '. URL: ' . $url, $optionsRequest);
            return false;
        }
    }
}
