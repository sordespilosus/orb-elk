<?php

use App\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    public function test_user_can_get_profile()
    {
        $user = factory(User::class)->create([
            'login' => 'test' . date('Ymd') . '-' . str_random(10),
            'fio' => 'test' . date('Ymd') . '-' . str_random(10),
            'city' => rand(1, 30)
        ]);

        Auth::login($user);
        $response = $this->json('GET', '/api/v2/profile');

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    public function test_user_can_get_cities()
    {
        $user = factory(User::class)->create([
            'login' => 'test' . date('Ymd') . '-' . str_random(10),
            'fio' => 'test' . date('Ymd') . '-' . str_random(10)
        ]);

        $city_patterns = ['', 'Орен', 'Ор', 'Бу'];

        Auth::login($user);
        $response = $this->json('GET',
            '/api/v2/cities?city=' . array_rand($city_patterns) . '&count=' . rand(1, 10)
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

    }
}
