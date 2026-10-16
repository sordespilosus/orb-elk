<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class UserControllerTest extends TestCase
{
    public function test_can_delete_profile_when_authorized()
    {
        $user = factory(User::class)->create([
            'login' => 'test' . date('Ymd') . '-' . str_random(10),
            'fio' => 'test' . date('Ymd') . '-' . str_random(10),
        ]);
        Auth::login($user);
        $response = $this->json('POST', '/api/v2/delete_profile');

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

}
