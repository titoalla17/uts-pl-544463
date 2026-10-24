<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_token_tidak_boleh_melihat_film(): void
    {
        $this->getJson('/api/films')->assertUnauthorized();
    }

    public function test_tanpa_token_dan_tanpa_header_accept_tetap_401_json(): void
    {
        $this->get('/api/films')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_login_menghasilkan_token_dan_logout_mencabutnya(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $token = $response->json('token');
        $this->withToken($token)->getJson('/api/films')->assertOk();
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        // Guard menyimpan user yang sudah di-resolve; reset agar request berikut memeriksa token ulang.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/films')->assertUnauthorized();
    }
}
