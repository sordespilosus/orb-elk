<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ImportUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $this->mock);
    }

    public function test_import_users_command()
    {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));

        DB::table('mailer.export')->update(['last_date' => 'now()']);
        DB::table('oauth.users')->insert([
            [
                'id' => (int)date('Ymd'),
                'email' => "test-$today-1@test.ru",
                'login' => "test-$today-1",
                'name' => "test-$today-1",
                'password' => 'test_password',
                'fio' => "test-$today-1",
                'created_at' => $tomorrow
            ],
            [
                'id' => (int)date('Ymd') + 1,
                'email' => "test-$today-2@test.ru",
                'login' => "test-$today-1",
                'name' => "test-$today-2",
                'password' => 'test_password',
                'fio' => "test-$today-2",
                'created_at' => $tomorrow
            ]
        ]);

        $response = new Response(200, [], json_encode(['data' => ['total' => 2]]));
        $this->mock->shouldReceive('post')->andReturn($response);

        $this->artisan('import:users');

        $this->assertFileExists(Storage::disk('public')->path("users_$today.csv"));
    }

    protected function tearDown(): void
    {
        DB::table('oauth.users')->delete((int)date('Ymd'));
        DB::table('oauth.users')->delete((int)date('Ymd') + 1);

        $auth = json_decode(config('listmonk.service_auth'));
        $client = new Client(['verify' => false]);

        $url = config('listmonk.service_url') . '/api/subscribers';

        $users = $client->get($url, ['auth' => $auth])->getBody()->getContents();
        $users = json_decode($users)->data->results;

        $client->delete($url . '/' . $users[0]->id, ['auth' => $auth]);
        $client->delete($url . '/' . $users[1]->id, ['auth' => $auth]);
    }

}
