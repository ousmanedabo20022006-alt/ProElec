<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visiteur_peut_creer_un_compte_proelec(): void
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Ousmane Test',
            'email' => 'ousmane.test@example.com',
            'password' => 'MotDePasse123!',
            'password_confirmation' => 'MotDePasse123!',
            'entreprise_nom' => 'Entreprise Test',
            'entreprise_email' => 'entreprise.test@example.com',
            'telephone' => '0612345678',
            'adresse' => '10 rue de Test',
            'code_postal' => '75001',
            'ville' => 'Paris',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'entreprise_id'],
                'entreprise' => ['id', 'nom', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Ousmane Test',
            'email' => 'ousmane.test@example.com',
        ]);

        $this->assertDatabaseHas('entreprises', [
            'nom' => 'Entreprise Test',
            'email' => 'entreprise.test@example.com',
        ]);

        $user = User::where('email', 'ousmane.test@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('MotDePasse123!', $user->password));
        $this->assertNotSame('MotDePasse123!', $user->password);
    }

    public function test_inscription_refusee_si_le_mot_de_passe_n_est_pas_confirme(): void
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Utilisateur Test',
            'email' => 'utilisateur.test@example.com',
            'password' => 'MotDePasse123!',
            'password_confirmation' => 'MotDePasseDifferent!',
            'entreprise_nom' => 'Entreprise Test',
            'entreprise_email' => 'entreprise.test@example.com',
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('entreprises', 0);
    }

    public function test_inscription_refusee_si_l_email_est_deja_utilise(): void
    {
        $entreprise = Entreprise::create([
            'nom' => 'Entreprise Existante',
            'email' => 'existante@example.com',
        ]);

        User::create([
            'name' => 'Utilisateur Existant',
            'email' => 'existant@example.com',
            'password' => Hash::make('MotDePasse123!'),
            'entreprise_id' => $entreprise->id,
        ]);

        $response = $this->postJson('/api/register', [
            'nom' => 'Nouvel Utilisateur',
            'email' => 'existant@example.com',
            'password' => 'AutreMotDePasse123!',
            'password_confirmation' => 'AutreMotDePasse123!',
            'entreprise_nom' => 'Nouvelle Entreprise',
            'entreprise_email' => 'nouvelle@example.com',
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('entreprises', 1);
    }
}