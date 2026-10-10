<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FactureController extends Controller
{
    /**
     * Liste les factures de l'entreprise connectée.
     */
    public function index(Request $request)
    {
        $entrepriseId = $request->user()->entreprise_id;

        $factures = Facture::with(['client', 'devis'])
            ->where('entreprise_id', $entrepriseId)
            ->orderByDesc('date_emission')
            ->orderByDesc('id')
            ->get();

        return response()->json($factures);
    }

    /**
     * Crée une facture avec un numéro généré automatiquement.
     */
    public function store(Request $request)
    {
        $entrepriseId = $request->user()->entreprise_id;

        $validated = $request->validate([
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('entreprise_id', $entrepriseId),
            ],

            'devis_id' => [
                'nullable',
                'integer',
                Rule::exists('devis', 'id')
                    ->where('entreprise_id', $entrepriseId)
                    ->where('client_id', $request->input('client_id')),
            ],

            'date_emission' => [
                'required',
                'date',
            ],

            'date_echeance' => [
                'nullable',
                'date',
                'after_or_equal:date_emission',
            ],

            'statut' => [
                'sometimes',
                Rule::in(['brouillon', 'envoyee', 'payee', 'annulee']),
            ],

            'montant_ht' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'taux_tva' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        // Calcul des montants côté serveur.
        $montantHt = round((float) $validated['montant_ht'], 2);
        $tauxTva = round((float) $validated['taux_tva'], 2);
        $montantTva = round($montantHt * $tauxTva / 100, 2);
        $montantTtc = round($montantHt + $montantTva, 2);

        // Création de la facture et génération du numéro.
        $facture = DB::transaction(function () use (
            $entrepriseId,
            $validated,
            $montantHt,
            $tauxTva,
            $montantTva,
            $montantTtc
        ) {
            $annee = substr($validated['date_emission'], 0, 4);

            $derniereFacture = Facture::where(
                'entreprise_id',
                $entrepriseId
            )
                ->where('numero', 'like', "FAC-{$annee}-%")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $dernierNumero = 0;

            if ($derniereFacture) {
                $parties = explode('-', $derniereFacture->numero);
                $dernierNumero = (int) end($parties);
            }

            $numero = sprintf(
                'FAC-%s-%03d',
                $annee,
                $dernierNumero + 1
            );

            return Facture::create([
                'entreprise_id' => $entrepriseId,
                'client_id' => $validated['client_id'],
                'devis_id' => $validated['devis_id'] ?? null,
                'numero' => $numero,
                'date_emission' => $validated['date_emission'],
                'date_echeance' => $validated['date_echeance'] ?? null,
                'statut' => $validated['statut'] ?? 'brouillon',
                'montant_ht' => $montantHt,
                'taux_tva' => $tauxTva,
                'montant_tva' => $montantTva,
                'montant_ttc' => $montantTtc,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return response()->json(
            $facture->load(['client', 'devis']),
            201
        );
    }

    /**
     * Modifie le statut d'une facture de l'entreprise connectée.
     */
    public function updateStatut(Request $request, int $id)
    {
        $entrepriseId = $request->user()->entreprise_id;

        // Recherche limitée à l'entreprise connectée.
        $facture = Facture::where('entreprise_id', $entrepriseId)
            ->where('id', $id)
            ->firstOrFail();

        // Validation des statuts autorisés.
        $validated = $request->validate([
            'statut' => [
                'required',
                Rule::in([
                    'brouillon',
                    'envoyee',
                    'payee',
                    'annulee',
                ]),
            ],
        ]);

        $facture->statut = $validated['statut'];
        $facture->save();

        return response()->json(
            $facture->load(['client', 'devis'])
        );
    }

    /**
     * Télécharge le PDF d'une facture de l'entreprise connectée.
     */
    public function pdf(Request $request, int $id)
    {
        $entrepriseId = $request->user()->entreprise_id;

        $facture = Facture::with(['client', 'devis'])
            ->where('entreprise_id', $entrepriseId)
            ->where('id', $id)
            ->firstOrFail();

        $entreprise = Entreprise::findOrFail($entrepriseId);

        abort_unless(
            $facture->client &&
            (int) $facture->client->entreprise_id === (int) $entrepriseId,
            404
        );

        $pdf = Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'entreprise' => $entreprise,
            'client' => $facture->client,
        ])->setPaper('a4', 'portrait');

        $nomFichier = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '_',
            $facture->numero
        );

        return $pdf->download($nomFichier . '.pdf');
    }
}