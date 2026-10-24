<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FilmApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_film_dengan_token(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $payload = [
            'judul' => 'Parasite', 'sutradara' => 'Bong Joon-ho', 'tahun_rilis' => 2019,
            'genre' => 'Thriller', 'rating' => 4.5, 'tanggal_ditonton' => '2026-10-22',
            'ulasan' => 'Tangga tidak pernah semenakutkan ini.', 'favorit' => true,
        ];

        $created = $this->postJson('/api/films', $payload)->assertCreated()
            ->assertJsonPath('data.judul', 'Parasite')
            ->assertJsonPath('data.rating', 4.5)
            ->assertJsonPath('data.favorit', true);
        $id = $created->json('data.id');

        $this->getJson('/api/films')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/films/$id")->assertOk()->assertJsonPath('data.sutradara', 'Bong Joon-ho');
        $this->putJson("/api/films/$id", [...$payload, 'rating' => 3.5, 'favorit' => false])
            ->assertOk()->assertJsonPath('data.rating', 3.5)->assertJsonPath('data.favorit', false);
        $this->deleteJson("/api/films/$id")->assertOk();
        $this->assertDatabaseMissing('films', ['id' => $id]);
    }

    public function test_film_tanpa_rating_dan_ulasan_tetap_valid(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/films', [
            'judul' => 'Dune: Part Two', 'sutradara' => 'Denis Villeneuve',
            'tahun_rilis' => 2024, 'genre' => 'Sci-Fi',
        ])->assertCreated()->assertJsonPath('data.rating', null)->assertJsonPath('data.favorit', false);
    }

    public function test_validasi_gagal_menghasilkan_422(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/films', [
            'judul' => '', 'sutradara' => '', 'tahun_rilis' => 1700, 'genre' => '', 'rating' => 4.3,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['judul', 'sutradara', 'tahun_rilis', 'genre', 'rating']);
    }

    public function test_film_milik_pengguna_lain_tidak_bisa_dilihat(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $film = $owner->films()->create([
            'judul' => 'Spirited Away', 'sutradara' => 'Hayao Miyazaki',
            'tahun_rilis' => 2001, 'genre' => 'Animasi',
        ]);
        Sanctum::actingAs($other);
        $this->getJson("/api/films/$film->id")->assertNotFound();
        $this->deleteJson("/api/films/$film->id")->assertNotFound();
        $this->assertDatabaseHas('films', ['id' => $film->id]);
    }
}
