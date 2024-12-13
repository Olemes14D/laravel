<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Chirp;
use Tests\TestCase;
use App\Models\User;

class ChirpTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_un_chirp_ne_peut_pas_avoir_un_contenu_vide()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $reponse = $this->post('/chirps', [

        'contenu' => ''
        ]);
        $reponse->assertSessionHasErrors(['contenu']);
        }
        public function test_un_chirp_ne_peut_pas_depasse_255_caracteres()
        {
        $user = User::factory()->create();
        $this->actingAs($user);
        $reponse = $this->post('/chirps', [
        'contenu' => str_repeat('a', 256)
        ]);
        $reponse->assertSessionHasErrors(['contenu']);
    }

    public function test_les_chirps_sont_affiches_sur_la_page_d_accueil()
    {

        $chirps = Chirp::factory()->count(3)->create();
        $reponse = $this->get('/');
        foreach ($chirps as $chirp) {
        $reponse->assertSee($chirp->contenu);
        }
    }

    public function test_un_utilisateur_peut_modifier_son_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
        'content' => 'Chirp modifié'
        ]);

        $reponse->assertStatus(200);
        // Vérifie si le chirp existe dans la base de donnée.
        $this->assertDatabaseHas('chirps', [
        'id' => $chirp->id,
        'content' => 'Chirp modifié',
        ]);
    }

        public function test_un_utilisateur_peut_supprimer_son_chirp
        ()
        {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->delete("/chirps/{$chirp->id}");
        $reponse->assertStatus(200);
    $this->assertDatabaseMissing('chirps', [
        'id' => $chirp->id,
        ]);
    }

    public function test_un_utilisateur_ne_peut_pas_modifier_ou_supprimer_le_chirp_d_un_autre_utilisateur()
    {
        // Créez deux utilisateurs
        $utilisateur1 = User::factory()->create();
        $utilisateur2 = User::factory()->create();

        // Créez un "chirp" associé à l'utilisateur 1
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur1->id]);

        // Simulez la connexion de l'utilisateur 2
        $this->actingAs($utilisateur2);

        // Tentez de supprimer le "chirp" de l'utilisateur 1
        $response = $this->delete("/chirps/{$chirp->id}");

        // Vérifiez que la suppression est interdite (403 Forbidden)
        $response->assertStatus(403);

        // Vérifiez que le "chirp" existe toujours dans la base de données
        $this->assertDatabaseHas('chirps', [
            'id' => $chirp->id,
        ]);
    }

    public function test_la_validation_est_appliquee_lors_de_la_mise_a_jour_d_un_chirp()
    {
        // Créez un utilisateur
        $utilisateur = User::factory()->create();

        // Créez un "chirp" associé à cet utilisateur
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);

        // Simulez la connexion de l'utilisateur
        $this->actingAs($utilisateur);

        // Tentez de mettre à jour le "chirp" avec un contenu vide
        $response = $this->put("/chirps/{$chirp->id}", [
            'content' => '',
        ]);

        // Vérifiez que la requête retourne un statut 422 (erreur de validation)
        $response->assertStatus(422);

        // Tentez de mettre à jour le "chirp" avec un contenu trop long
        $longContent = str_repeat('a', 300); // Supposons une limite de 255 caractères
        $response = $this->put("/chirps/{$chirp->id}", [
            'content' => $longContent,
        ]);

        // Vérifiez que la requête retourne un statut 422 (erreur de validation)
        $response->assertStatus(422);
    }

    public function test_un_utilisateur_ne_peut_pas_creer_plus_de_10_chirps()
    {
        // Créez un utilisateur
        $utilisateur = User::factory()->create();

        // Créez 10 "chirps" pour cet utilisateur
        Chirp::factory()->count(10)->create(['user_id' => $utilisateur->id]);

        // Simulez la connexion de l'utilisateur
        $this->actingAs($utilisateur);

        // Tentez de créer un 11e "chirp"
        $response = $this->post('/chirps', [
            'content' => 'Chirp supplémentaire',
        ]);

        // Vérifiez que la requête retourne un statut 403 (interdiction)
        $response->assertStatus(403);

        // Vérifiez que le "chirp" supplémentaire n'a pas été ajouté
        $this->assertDatabaseCount('chirps', 10);
    }

    public function test_afficher_uniquement_les_chirps_des_7_derniers_jours()
    {
        // Créez un utilisateur
        $utilisateur = User::factory()->create();

        // Créez des "chirps" à différentes dates
        Chirp::factory()->create(['user_id' => $utilisateur->id, 'created_at' => now()->subDays(10)]);
        Chirp::factory()->create(['user_id' => $utilisateur->id, 'created_at' => now()->subDays(5)]);

        // Simulez la connexion de l'utilisateur
        $this->actingAs($utilisateur);

        // Effectuez une requête GET pour récupérer les "chirps"
        $response = $this->get('/chirps');

        // Vérifiez que seuls les "chirps" récents (moins de 7 jours) sont affichés
        $response->assertStatus(200);
        $response->assertSeeTextInOrder(['Chirp récent']);
        $response->assertDontSeeText('Chirp ancien');
    }

    public function test_un_utilisateur_peut_liker_un_chirp()
    {
        // Créez un utilisateur
        $utilisateur = User::factory()->create();

        // Créez un "chirp"
        $chirp = Chirp::factory()->create();

        // Simulez la connexion de l'utilisateur
        $this->actingAs($utilisateur);

        // Liker le "chirp"
        $response = $this->post("/chirps/{$chirp->id}/like");

        // Vérifiez que le like est enregistré en base de données
        $response->assertStatus(200);
        $this->assertDatabaseHas('likes', [
            'user_id' => $utilisateur->id,
            'chirp_id' => $chirp->id,
        ]);

        // Tentez de liker à nouveau le même "chirp"
        $response = $this->post("/chirps/{$chirp->id}/like");

        // Vérifiez que le second like n'est pas accepté
        $response->assertStatus(403);
    }




}


