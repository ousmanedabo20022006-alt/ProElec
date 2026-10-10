<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Devis;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsolationFacturesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CrÃ©e une entreprise et son utilisateur.
     */
    private function creerEntrepriseEtUtilisateur(string $suffixe): array
    {
        $entreprise = Entreprise::create([
            'nom' => 'Entreprise Facture ' . $suffixe,
            'email' => 'entreprise.facture.' . $suffixe . '@example.com',
        ]);

        $utilisateur = User::create([
            'name' => 'Utilisateur Facture ' . $suffixe,
            'email' => 'utilisateur.facture.' . $suffixe . '@example.com',
            'password' => Hash::make('MotDePasse123!'),
            'entreprise_id' => $entreprise->id,
        ]);

        return [$entreprise, $utilisateur];
    }

    /**
     * CrÃ©e un client appartenant Ã  une entreprise.
     */
    private function creerClient(
        Entreprise $entreprise,
        string $suffixe
    ): Client {
        return Client::create([
            'entreprise_id' => $entreprise->id,
            'nom' => 'Client ' . $suffixe,
            'prenom' => 'Test',
            'email' => 'client.facture.' . $suffixe . '@example.com',
            'telephone' => '0600000000',
            'adresse' => '1 rue du Test',
            'code_postal' => '75000',
            'ville' => 'Paris',
        ]);
    }

    /**
     * CrÃ©e un devis appartenant Ã  une entreprise et Ã  un client.
     */
    private function creerDevis(
        Entreprise $entreprise,
        Client $client,
        string $suffixe
    ): Devis {
        return Devis::create([
            'entreprise_id' => $entreprise->id,
            'client_id' => $client->id,
            'numero' => 'DEV-FACT-' . $suffixe,
            'date_emission' => '2026-10-01',
            'date_validite' => '2026-11-01',
            'statut' => 'brouillon',
            'montant_ht' => 100,
            'taux_tva' => 20,
            'montant_tva' => 20,
            'montant_ttc' => 120,
        ]);
    }

    /**
     * CrÃ©e une facture appartenant Ã  une entreprise.
     */
    private function creerFacture(
        Entreprise $entreprise,
        Client $client,
        string $suffixe,
        ?Devis $devis = null
    ): Facture {
        return Facture::create([
            'entreprise_id' => $entreprise->id,
            'client_id' => $client->id,
            'devis_id' => $devis?->id,
            'numero' => 'FAC-2026-' . $suffixe,
            'date_emission' => '2026-10-01',
            'date_echeance' => '2026-11-01',
            'statut' => 'brouillon',
            'montant_ht' => 100,
            'taux_tva' => 20,
            'montant_tva' => 20,
            'montant_ttc' => 120,
        ]);
    }

    /**
     * VÃ©rifie qu'une entreprise ne voit que ses propres factures.
     */
    public function test_une_entreprise_ne_voit_que_ses_propres_factures(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur('A');

        [$entrepriseB] =
            $this->creerEntrepriseEtUtilisateur('B');

        $clientA = $this->creerClient($entrepriseA, 'A');
        $clientB = $this->creerClient($entrepriseB, 'B');

        $factureA = $this->creerFacture(
            $entrepriseA,
            $clientA,
            '001'
        );

        $this->creerFacture(
            $entrepriseB,
            $clientB,
            '002'
        );

        $reponse = $this->actingAs($utilisateurA, 'sanctum')
            ->getJson('/api/factures');

        $reponse->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $factureA->id,
            ]);
    }

    /**
     * VÃ©rifie qu'une entreprise ne peut pas modifier
     * le statut d'une facture appartenant Ã  une autre entreprise.
     */
    public function test_une_entreprise_ne_peut_pas_modifier_le_statut_d_une_facture_etrangere(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur('C');

        [$entrepriseB] =
            $this->creerEntrepriseEtUtilisateur('D');

        $clientB = $this->creerClient($entrepriseB, 'D');

        $factureB = $this->creerFacture(
            $entrepriseB,
            $clientB,
            '003'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->patchJson(
                '/api/factures/' . $factureB->id . '/statut',
                [
                    'statut' => 'payee',
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('factures', [
            'id' => $factureB->id,
            'entreprise_id' => $entrepriseB->id,
            'statut' => 'brouillon',
        ]);
    }

    /**
     * VÃ©rifie qu'une entreprise ne peut pas tÃ©lÃ©charger
     * le PDF d'une facture appartenant Ã  une autre entreprise.
     */
    public function test_une_entreprise_ne_peut_pas_telecharger_le_pdf_d_une_facture_etrangere(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur('E');

        [$entrepriseB] =
            $this->creerEntrepriseEtUtilisateur('F');

        $clientB = $this->creerClient($entrepriseB, 'F');

        $factureB = $this->creerFacture(
            $entrepriseB,
            $clientB,
            '004'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->get('/api/factures/' . $factureB->id . '/pdf')
            ->assertNotFound();
    }

    /**
     * VÃ©rifie qu'une entreprise ne peut pas crÃ©er
     * une facture pour le client d'une autre entreprise.
     */
    public function test_une_entreprise_ne_peut_pas_creer_une_facture_pour_le_client_d_une_autre(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur('G');

        [$entrepriseB] =
            $this->creerEntrepriseEtUtilisateur('H');

        $clientB = $this->creerClient($entrepriseB, 'H');

        $this->actingAs($utilisateurA, 'sanctum')
            ->postJson('/api/factures', [
                'client_id' => $clientB->id,
                'date_emission' => '2026-10-01',
                'montant_ht' => 100,
                'taux_tva' => 20,
            ])
            ->assertUnprocessable();
    }

    /**
     * VÃ©rifie qu'une entreprise ne peut pas crÃ©er
     * une facture associÃ©e au devis d'une autre entreprise.
     */
    public function test_une_entreprise_ne_peut_pas_creer_une_facture_avec_le_devis_d_une_autre(): void
    {
        [$entrepriseA, $utilisateurA] =
            $this->creerEntrepriseEtUtilisateur('I');

        [$entrepriseB] =
            $this->creerEntrepriseEtUtilisateur('J');

        $clientA = $this->creerClient($entrepriseA, 'I');
        $clientB = $this->creerClient($entrepriseB, 'J');

        $devisB = $this->creerDevis(
            $entrepriseB,
            $clientB,
            'J'
        );

        $this->actingAs($utilisateurA, 'sanctum')
            ->postJson('/api/factures', [
                'client_id' => $clientA->id,
                'devis_id' => $devisB->id,
                'date_emission' => '2026-10-01',
                'montant_ht' => 100,
                'taux_tva' => 20,
            ])
            ->assertUnprocessable();
    }

    /**
     * Verifie que les numeros des factures sont generes automatiquement.
     */
    public function test_la_numerotation_des_factures_est_automatique(): void
    {
        [$entreprise, $utilisateur] =
            $this->creerEntrepriseEtUtilisateur('NUM');

        $client = $this->creerClient($entreprise, 'NUM');

        $this->actingAs($utilisateur, 'sanctum');

        $this->postJson('/api/factures', [
            'client_id' => $client->id,
            'date_emission' => '2026-10-01',
            'montant_ht' => 100,
            'taux_tva' => 20,
        ])
            ->assertCreated()
            ->assertJsonPath('numero', 'FAC-2026-001');

        $this->postJson('/api/factures', [
            'client_id' => $client->id,
            'date_emission' => '2026-10-02',
            'montant_ht' => 200,
            'taux_tva' => 20,
        ])
            ->assertCreated()
            ->assertJsonPath('numero', 'FAC-2026-002');
    }

    /**
     * Verifie les calculs HT, TVA et TTC lors de la creation.
     */
    public function test_les_montants_de_la_facture_sont_calcules_correctement(): void
    {
        [$entreprise, $utilisateur] =
            $this->creerEntrepriseEtUtilisateur('CALC');

        $client = $this->creerClient($entreprise, 'CALC');

        $this->actingAs($utilisateur, 'sanctum')
            ->postJson('/api/factures', [
                'client_id' => $client->id,
                'date_emission' => '2026-10-03',
                'montant_ht' => 100,
                'taux_tva' => 20,
            ])
            ->assertCreated()
            ->assertJsonPath('montant_ht', '100.00')
            ->assertJsonPath('taux_tva', '20.00')
            ->assertJsonPath('montant_tva', '20.00')
            ->assertJsonPath('montant_ttc', '120.00');
    }
}