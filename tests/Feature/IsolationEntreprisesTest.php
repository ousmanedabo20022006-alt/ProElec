<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsolationEntreprisesTest extends TestCase
{
    use RefreshDatabase;

    private function creerEntrepriseEtUtilisateur(
        string $nom,
        string $email
    ): array {
        $entreprise = Entreprise::create([
            'nom' => $nom,
            'email' => 'entreprise.' . $email,
        ]);

        $utilisateur = User::create([
            'name' => $nom,
            'email' => $email,
            'password' => 'MotDePasse123!',
            'entreprise_id' => $entreprise->id,
        ]);

        return [$entreprise, $utilisateur];
    }

    private function creerClient(
        int $entrepriseId,
        string $nom
    ): Client {
        return Client::create([
            'entreprise_id' => $entrepriseId,
            'nom' => $nom,
            'prenom' => 'Test',
            'email' => null,
        ]);
    }

    public function test_une_entreprise_ne_voit_que_ses_propres_clients(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
                'Entreprise A',
                'a@example.com'
            );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $this->creerClient($entrepriseA->id, 'Client A');
        $this->creerClient($entrepriseB->id, 'Client B');

        $response = $this->actingAs($utilisateurA, 'web')
            ->getJson('/api/clients');

        $response->assertOk()
            ->assertJsonCount(1, 'clients')
            ->assertJsonFragment(['nom' => 'Client A'])
            ->assertJsonMissing(['nom' => 'Client B']);
    }

    public function test_une_entreprise_ne_peut_pas_modifier_le_client_d_une_autre(): void
    {
        [, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientB = $this->creerClient(
            $entrepriseB->id,
            'Client B'
        );

        $response = $this->actingAs($utilisateurA, 'web')
            ->putJson('/api/clients/' . $clientB->id, [
                'nom' => 'Client piraté',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('clients', [
            'id' => $clientB->id,
            'nom' => 'Client B',
            'entreprise_id' => $entrepriseB->id,
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_supprimer_le_client_d_une_autre(): void
    {
        [, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientB = $this->creerClient(
            $entrepriseB->id,
            'Client B'
        );

        $response = $this->actingAs($utilisateurA, 'web')
            ->deleteJson('/api/clients/' . $clientB->id);

        $response->assertNotFound();

        $this->assertDatabaseHas('clients', [
            'id' => $clientB->id,
            'entreprise_id' => $entrepriseB->id,
        ]);
    }

    public function test_la_creation_d_un_client_utilise_l_entreprise_connectee(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
                'Entreprise A',
                'a@example.com'
            );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $response = $this->actingAs($utilisateurA, 'web')
            ->postJson('/api/clients', [
                'nom' => 'Nouveau client',
                'entreprise_id' => $entrepriseB->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('clients', [
            'nom' => 'Nouveau client',
            'entreprise_id' => $entrepriseA->id,
        ]);

        $this->assertDatabaseMissing('clients', [
            'nom' => 'Nouveau client',
            'entreprise_id' => $entrepriseB->id,
        ]);
    }
}