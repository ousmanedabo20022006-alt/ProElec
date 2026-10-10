<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Devis;
use App\Models\Entreprise;
use App\Models\Intervention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsolationDevisTest extends TestCase
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

    private function creerDevis(
        int $entrepriseId,
        int $clientId,
        ?int $interventionId,
        string $numero
    ): Devis {
        return Devis::create([
            'entreprise_id' => $entrepriseId,
            'client_id' => $clientId,
            'intervention_id' => $interventionId,
            'numero' => $numero,
            'date_emission' => '2026-10-01',
            'date_validite' => '2026-11-01',
            'statut' => 'brouillon',
            'montant_ht' => 100,
            'taux_tva' => 20,
            'montant_tva' => 20,
            'montant_ttc' => 120,
            'notes' => 'Devis de test',
        ]);
    }

    public function test_une_entreprise_ne_voit_que_ses_propres_devis(): void
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

        $clientA = $this->creerClient(
            $entrepriseA->id,
            'Client A'
        );

        $clientB = $this->creerClient(
            $entrepriseB->id,
            'Client B'
        );

        $this->creerDevis(
            $entrepriseA->id,
            $clientA->id,
            null,
            'DEV-A-001'
        );

        $this->creerDevis(
            $entrepriseB->id,
            $clientB->id,
            null,
            'DEV-B-001'
        );

        $response = $this->actingAs($utilisateurA, 'sanctum')
            ->getJson('/api/devis');

        $response->assertOk()
            ->assertJsonFragment(['numero' => 'DEV-A-001'])
            ->assertJsonMissing(['numero' => 'DEV-B-001']);
    }

    public function test_une_entreprise_ne_peut_pas_consulter_le_devis_d_une_autre(): void
    {
        [, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
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

        $devisB = $this->creerDevis(
            $entrepriseB->id,
            $clientB->id,
            null,
            'DEV-B-002'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->getJson('/api/devis/' . $devisB->id)
            ->assertNotFound();
    }

    public function test_une_entreprise_ne_peut_pas_modifier_le_devis_d_une_autre(): void
    {
        [, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
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

        $devisB = $this->creerDevis(
            $entrepriseB->id,
            $clientB->id,
            null,
            'DEV-B-003'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->putJson('/api/devis/' . $devisB->id, [
                'notes' => 'Modification interdite',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('devis', [
            'id' => $devisB->id,
            'notes' => 'Devis de test',
            'entreprise_id' => $entrepriseB->id,
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_supprimer_le_devis_d_une_autre(): void
    {
        [, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
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

        $devisB = $this->creerDevis(
            $entrepriseB->id,
            $clientB->id,
            null,
            'DEV-B-004'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->deleteJson('/api/devis/' . $devisB->id)
            ->assertNotFound();

        $this->assertDatabaseHas('devis', [
            'id' => $devisB->id,
            'entreprise_id' => $entrepriseB->id,
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_creer_un_devis_pour_le_client_d_une_autre(): void
    {
        [, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
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

        $this->actingAs($utilisateurA, 'sanctum')
            ->postJson('/api/devis', [
                'client_id' => $clientB->id,
                'numero' => 'DEV-INTERDIT-001',
                'date_emission' => '2026-10-01',
                'date_validite' => '2026-11-01',
                'montant_ht' => 100,
                'taux_tva' => 20,
                'montant_tva' => 20,
                'montant_ttc' => 120,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('devis', [
            'numero' => 'DEV-INTERDIT-001',
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_creer_un_devis_pour_l_intervention_d_une_autre(): void
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

        $clientA = $this->creerClient(
            $entrepriseA->id,
            'Client A'
        );

        $clientB = $this->creerClient(
            $entrepriseB->id,
            'Client B'
        );

        $interventionB = $this->creerIntervention(
            $entrepriseB->id,
            $clientB->id,
            'Intervention B'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->postJson('/api/devis', [
                'client_id' => $clientA->id,
                'intervention_id' => $interventionB->id,
                'numero' => 'DEV-INTERDIT-002',
                'date_emission' => '2026-10-01',
                'montant_ht' => 100,
                'taux_tva' => 20,
                'montant_tva' => 20,
                'montant_ttc' => 120,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('devis', [
            'numero' => 'DEV-INTERDIT-002',
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_associer_son_devis_au_client_d_une_autre(): void
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

        $clientA = $this->creerClient(
            $entrepriseA->id,
            'Client A'
        );

        $clientB = $this->creerClient(
            $entrepriseB->id,
            'Client B'
        );

        $devisA = $this->creerDevis(
            $entrepriseA->id,
            $clientA->id,
            null,
            'DEV-A-007'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->putJson('/api/devis/' . $devisA->id, [
                'client_id' => $clientB->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('devis', [
            'id' => $devisA->id,
            'client_id' => $clientA->id,
            'entreprise_id' => $entrepriseA->id,
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_associer_son_devis_a_l_intervention_d_une_autre(): void
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

        $clientA = $this->creerClient(
            $entrepriseA->id,
            'Client A'
        );

        $clientB = $this->creerClient(
            $entrepriseB->id,
            'Client B'
        );

        $interventionB = $this->creerIntervention(
            $entrepriseB->id,
            $clientB->id,
            'Intervention B'
        );

        $devisA = $this->creerDevis(
            $entrepriseA->id,
            $clientA->id,
            null,
            'DEV-A-008'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->putJson('/api/devis/' . $devisA->id, [
                'intervention_id' => $interventionB->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('devis', [
            'id' => $devisA->id,
            'intervention_id' => null,
            'entreprise_id' => $entrepriseA->id,
        ]);
    }

    public function test_une_entreprise_ne_peut_pas_telecharger_le_pdf_du_devis_d_une_autre(): void
    {
        [, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur(
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

        $devisB = $this->creerDevis(
            $entrepriseB->id,
            $clientB->id,
            null,
            'DEV-B-009'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->get('/api/devis/' . $devisB->id . '/pdf')
            ->assertNotFound();
    }

    public function test_une_entreprise_peut_telecharger_le_pdf_de_son_propre_devis(): void
    {
        [$entreprise, $utilisateur] =
            $this->creerEntrepriseEtUtilisateur(
                'Entreprise PDF Test',
                'pdf@example.com'
            );

        $client = $this->creerClient(
            $entreprise->id,
            'Client PDF Test'
        );

        $devis = $this->creerDevis(
            $entreprise->id,
            $client->id,
            null,
            'DEV-PDF-001'
        );

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->get('/api/devis/' . $devis->id . '/pdf');

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'application/pdf'
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );
    }
}