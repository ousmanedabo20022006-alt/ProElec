<?php

namespace App\Http\Controllers;

use App\Models\Devis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DevisController extends Controller
{
    public function index(Request $request)
    {
        $devis = Devis::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->with(['client', 'intervention'])
        ->orderByDesc('date_emission')
        ->get();

        return response()->json([
            'devis' => $devis,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $devis = Devis::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->with(['client', 'intervention'])
        ->first();

        if (!$devis) {
            return response()->json([
                'message' => 'Devis introuvable.',
            ], 404);
        }

        return response()->json([
            'devis' => $devis,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],

            'intervention_id' => [
                'nullable',
                'integer',
                'exists:interventions,id',
            ],

            'numero' => [
                'required',
                'string',
                'max:50',
                'unique:devis,numero',
            ],

            'date_emission' => [
                'required',
                'date',
            ],

            'date_validite' => [
                'nullable',
                'date',
                'after_or_equal:date_emission',
            ],

            'statut' => [
                'nullable',
                'string',
                'in:brouillon,envoye,accepte,refuse,expire',
            ],

            'montant_ht' => [
                'required',
                'numeric',
                'min:0',
            ],

            'taux_tva' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'montant_tva' => [
                'required',
                'numeric',
                'min:0',
            ],

            'montant_ttc' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier que le client appartient à la même entreprise.
        $clientExists = $request->user()
            ->entreprise
            ->clients()
            ->where('id', $request->client_id)
            ->exists();

        if (!$clientExists) {
            return response()->json([
                'message' => 'Client introuvable.',
            ], 404);
        }

        // Vérifier que l'intervention appartient à la même entreprise.
        if ($request->filled('intervention_id')) {
            $interventionExists = $request->user()
                ->entreprise
                ->interventions()
                ->where('id', $request->intervention_id)
                ->exists();

            if (!$interventionExists) {
                return response()->json([
                    'message' => 'Intervention introuvable.',
                ], 404);
            }
        }

        $devis = Devis::create([
            'entreprise_id' => $request->user()->entreprise_id,
            'client_id' => $request->client_id,
            'intervention_id' => $request->intervention_id,
            'numero' => $request->numero,
            'date_emission' => $request->date_emission,
            'date_validite' => $request->date_validite,
            'statut' => $request->statut ?? 'brouillon',
            'montant_ht' => $request->montant_ht,
            'taux_tva' => $request->taux_tva,
            'montant_tva' => $request->montant_tva,
            'montant_ttc' => $request->montant_ttc,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Devis créé avec succès.',
            'devis' => $devis->load(['client', 'intervention']),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $devis = Devis::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->first();

        if (!$devis) {
            return response()->json([
                'message' => 'Devis introuvable.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:clients,id',
            ],

            'intervention_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:interventions,id',
            ],

            'numero' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'unique:devis,numero,' . $devis->id,
            ],

            'date_emission' => [
                'sometimes',
                'required',
                'date',
            ],

            'date_validite' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:date_emission',
            ],

            'statut' => [
                'sometimes',
                'nullable',
                'string',
                'in:brouillon,envoye,accepte,refuse,expire',
            ],

            'montant_ht' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'taux_tva' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'montant_tva' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'montant_ttc' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('client_id')) {
            $clientExists = $request->user()
                ->entreprise
                ->clients()
                ->where('id', $request->client_id)
                ->exists();

            if (!$clientExists) {
                return response()->json([
                    'message' => 'Client introuvable.',
                ], 404);
            }
        }

        if (
            $request->has('intervention_id')
            && $request->filled('intervention_id')
        ) {
            $interventionExists = $request->user()
                ->entreprise
                ->interventions()
                ->where('id', $request->intervention_id)
                ->exists();

            if (!$interventionExists) {
                return response()->json([
                    'message' => 'Intervention introuvable.',
                ], 404);
            }
        }

        $devis->update($request->only([
            'client_id',
            'intervention_id',
            'numero',
            'date_emission',
            'date_validite',
            'statut',
            'montant_ht',
            'taux_tva',
            'montant_tva',
            'montant_ttc',
            'notes',
        ]));

        return response()->json([
            'message' => 'Devis modifié avec succès.',
            'devis' => $devis->fresh()->load([
                'client',
                'intervention',
            ]),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $devis = Devis::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->first();

        if (!$devis) {
            return response()->json([
                'message' => 'Devis introuvable.',
            ], 404);
        }

        $devis->delete();

        return response()->json([
            'message' => 'Devis supprimé avec succès.',
        ]);
    }

    public function pdf(Request $request, int $id)
    {
        $devis = Devis::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->with(['client', 'intervention'])
        ->first();

        if (!$devis) {
            return response()->json([
                'message' => 'Devis introuvable.',
            ], 404);
        }

        $pdf = Pdf::loadView('pdf.devis', [
            'devis' => $devis,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download(
            'devis-' . $devis->numero . '.pdf'
        );
    }
}