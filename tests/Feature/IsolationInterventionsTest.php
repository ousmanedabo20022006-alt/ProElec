<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Intervention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsolationInterventionsTest extends TestCase
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

    private function creerIntervention(
        int $entrepriseId,
        int $clientId,
        string $titre
    ): Intervention {
        return Intervention::create([
            'entreprise_id' => $entrepriseId,
            'client_id' => $clientId,
            'titre' => $titre,
            'description' => 'Intervention de test',
            'statut' => 'planifiee',
        ]);
    }

    /**
     * Une entreprise ne voit que ses propres interventions.
     */
    public function test_une_entreprise_ne_voit_que_ses_propres_interventions(): void
    {
        [$entrepriseA, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientA = $this->creerClient($entrepriseA->id, 'Client A');
        $clientB = $this->creerClient($entrepriseB->id, 'Client B');

        $this->creerIntervention(
            $entrepriseA->id,
            $clientA->id,
            'Intervention A'
        );

        $this->creerIntervention(
            $entrepriseB->id,
            $clientB->id,
            'Intervention B'
        );

        $response = $this->actingAs($utilisateurA, 'sanctum')
            ->getJson('/api/interventions');

        $response->assertOk()
            ->assertJsonFragment(['titre' => 'Intervention A'])
            ->assertJsonMissing(['titre' => 'Intervention B']);
    }

    /**
     * Une entreprise ne peut pas modifier l'intervention d'une autre.
     */
    public function test_une_entreprise_ne_peut_pas_modifier_l_intervention_d_une_autre(): void
    {
        [, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientB = $this->creerClient($entrepriseB->id, 'Client B');

        $interventionB = $this->creerIntervention(
            $entrepriseB->id,
            $clientB->id,
            'Intervention B'
        );

        $response = $this->actingAs($utilisateurA, 'sanctum')
            ->putJson('/api/interventions/' . $interventionB->id, [
                'titre' => 'Intervention piratée',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('interventions', [
            'id' => $interventionB->id,
            'titre' => 'Intervention B',
            'entreprise_id' => $entrepriseB->id,
        ]);
    }

    /**
     * Une entreprise ne peut pas supprimer l'intervention d'une autre.
     */
    public function test_une_entreprise_ne_peut_pas_supprimer_l_intervention_d_une_autre(): void
    {
        [, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientB = $this->creerClient($entrepriseB->id, 'Client B');

        $interventionB = $this->creerIntervention(
            $entrepriseB->id,
            $clientB->id,
            'Intervention B'
        );

        $response = $this->actingAs($utilisateurA, 'sanctum')
            ->deleteJson('/api/interventions/' . $interventionB->id);

        $response->assertNotFound();

        $this->assertDatabaseHas('interventions', [
            'id' => $interventionB->id,
            'entreprise_id' => $entrepriseB->id,
        ]);
    }

    /**
     * Une entreprise ne peut pas créer une intervention
     * pour le client d'une autre entreprise.
     */
    public function test_une_entreprise_ne_peut_pas_creer_une_intervention_pour_le_client_d_une_autre(): void
    {
        [, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientB = $this->creerClient($entrepriseB->id, 'Client B');

        $response = $this->actingAs($utilisateurA, 'sanctum')
            ->postJson('/api/interventions', [
                'client_id' => $clientB->id,
                'titre' => 'Intervention interdite',
                'description' => 'Test de sécurité',
                'statut' => 'planifiee',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('interventions', [
            'titre' => 'Intervention interdite',
        ]);
    }

    /**
     * Une entreprise ne peut pas associer son intervention
     * au client d'une autre entreprise.
     */
    public function test_une_entreprise_ne_peut_pas_associer_son_intervention_au_client_d_une_autre(): void
    {
        [$entrepriseA, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientA = $this->creerClient($entrepriseA->id, 'Client A');
        $clientB = $this->creerClient($entrepriseB->id, 'Client B');

        $interventionA = $this->creerIntervention(
            $entrepriseA->id,
            $clientA->id,
            'Intervention A'
        );

        $response = $this->actingAs($utilisateurA, 'sanctum')
            ->putJson('/api/interventions/' . $interventionA->id, [
                'client_id' => $clientB->id,
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('interventions', [
            'id' => $interventionA->id,
            'client_id' => $clientA->id,
            'entreprise_id' => $entrepriseA->id,
        ]);
    }

    /**
     * Un utilisateur ne peut pas transférer une intervention
     * vers une autre entreprise en envoyant entreprise_id.
     */
    public function test_un_utilisateur_ne_peut_pas_changer_l_entreprise_d_une_intervention(): void
    {
        [$entrepriseA, $utilisateurA] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise A',
            'a@example.com'
        );

        [$entrepriseB] = $this->creerEntrepriseEtUtilisateur(
            'Entreprise B',
            'b@example.com'
        );

        $clientA = $this->creerClient($entrepriseA->id, 'Client A');

        $interventionA = $this->creerIntervention(
            $entrepriseA->id,
            $clientA->id,
            'Intervention A'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->putJson('/api/interventions/' . $interventionA->id, [
                'titre' => 'Intervention modifiée',
                'entreprise_id' => $entrepriseB->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('interventions', [
            'id' => $interventionA->id,
            'entreprise_id' => $entrepriseA->id,
            'titre' => 'Intervention modifiée',
        ]);
    }

    /**
     * Un visiteur non connecté ne peut pas consulter les interventions.
     */
    public function test_un_visiteur_non_connecte_ne_peut_pas_lister_les_interventions(): void
    {
        $this->getJson('/api/interventions')
            ->assertUnauthorized();
    }
}