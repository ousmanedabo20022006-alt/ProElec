<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthentificationTest extends TestCase
{
    use RefreshDatabase;

    private function creerUtilisateur(): User
    {
        $entreprise = Entreprise::create([
            'nom' => 'Entreprise Auth Test',
            'email' => 'entreprise.auth@example.com',
        ]);

        return User::create([
            'name' => 'Utilisateur Auth Test',
            'email' => 'auth.test@example.com',
            'password' => Hash::make('MotDePasse123!'),
            'entreprise_id' => $entreprise->id,
        ]);
    }

    public function test_un_utilisateur_peut_se_connecter_avec_ses_bons_identifiants(): void
    {
        $this->creerUtilisateur();

        $response = $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/login', [
                'email' => 'auth.test@example.com',
                'password' => 'MotDePasse123!',
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'entreprise_id'],
                'entreprise',
            ])
            ->assertJson([
                'message' => 'Connexion réussie.',
            ]);
    }

    public function test_la_connexion_est_refusee_avec_un_mauvais_mot_de_passe(): void
    {
        $this->creerUtilisateur();

        $response = $this->postJson('/api/login', [
            'email' => 'auth.test@example.com',
            'password' => 'MauvaisMotDePasse!',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Adresse e-mail ou mot de passe incorrect.',
            ]);
    }

    public function test_la_connexion_est_refusee_si_les_champs_sont_invalides(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'adresse-invalide',
            'password' => '',
        ]);

        $response->assertUnprocessable();
    }

    public function test_un_visiteur_ne_peut_pas_acceder_a_son_profil(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_un_utilisateur_connecte_peut_acceder_a_son_profil(): void
    {
        $user = $this->creerUtilisateur();

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'Utilisateur Auth Test',
                'email' => 'auth.test@example.com',
                'entreprise_id' => $user->entreprise_id,
            ]);
    }

    public function test_un_utilisateur_connecte_peut_se_deconnecter(): void
    {
        $user = $this->creerUtilisateur();

        $this->withMiddleware();
        $this->withSession([]);
        $this->actingAs($user, 'web');

        $response = $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Déconnexion réussie.',
            ]);

        $this->assertGuest('web');
    }

    public function test_un_utilisateur_deconnecte_ne_peut_plus_acceder_a_son_profil(): void
    {
        $user = $this->creerUtilisateur();

        $this->actingAs($user, 'web');

        $this->postJson('/api/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Déconnexion réussie.',
            ]);

        $this->assertGuest('web');

        // Réinitialise les guards mémorisés dans le test.
        auth()->forgetGuards();

        $this->getJson('/api/user')
            ->assertUnauthorized();
    }
}