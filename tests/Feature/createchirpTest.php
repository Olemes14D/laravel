<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Chirp;

class createchirpTest extends TestCase
{
  


    public function test_un_utilisateur_peut_creer_un_chirp()
    {
        // Simuler un utilisateur connecté
        $user = User::factory()->create();
        $this->actingAs($user);
        // Envoyer une requête POST pour créer un chirp

        $reponse = $this->post('/chirps', [
        'content' => 'Mon premier chirp !'
        ]);
        // Vérifier que le chirp a été ajouté à la base de donnée

        $reponse->assertStatus(201);
        $this->assertDatabaseHas('chirps', [
        'content' => 'Mon premier chirp !',
        'user_id' => $user->id,
        ]);
    }
}
