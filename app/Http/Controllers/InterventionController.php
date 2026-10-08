<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InterventionController extends Controller
{
    /**
     * Lister les interventions de l'entreprise de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $interventions = Intervention::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->with('client')
        ->orderBy('date_prevue')
        ->get();

        return response()->json([
            'interventions' => $interventions,
        ]);
    }

    /**
     * Créer une intervention pour l'entreprise de l'utilisateur connecté.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['nullable', 'string', 'max:255'],
            'date_prevue' => ['nullable', 'date'],
            'statut' => [
                'nullable',
                'string',
                'in:planifiee,en_cours,terminee,annulee',
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

        $intervention = Intervention::create([
            'entreprise_id' => $request->user()->entreprise_id,
            'client_id' => $request->client_id,
            'titre' => $request->titre,
            'description' => $request->description,
            'adresse' => $request->adresse,
            'code_postal' => $request->code_postal,
            'ville' => $request->ville,
            'date_prevue' => $request->date_prevue,
            'statut' => $request->statut ?? 'planifiee',
        ]);

        return response()->json([
            'message' => 'Intervention créée avec succès.',
            'intervention' => $intervention->load('client'),
        ], 201);
    }

    /**
     * Modifier une intervention appartenant à l'entreprise de l'utilisateur.
     */
    public function update(Request $request, int $id)
    {
        $intervention = Intervention::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->first();

        if (!$intervention) {
            return response()->json([
                'message' => 'Intervention introuvable.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => ['sometimes', 'required', 'integer', 'exists:clients,id'],
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'adresse' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code_postal' => ['sometimes', 'nullable', 'string', 'max:10'],
            'ville' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_prevue' => ['sometimes', 'nullable', 'date'],
            'statut' => [
                'sometimes',
                'nullable',
                'string',
                'in:planifiee,en_cours,terminee,annulee',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Si le client est modifié, vérifier qu'il appartient à la même entreprise.
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

        $intervention->update($request->only([
            'client_id',
            'titre',
            'description',
            'adresse',
            'code_postal',
            'ville',
            'date_prevue',
            'statut',
        ]));

        return response()->json([
            'message' => 'Intervention modifiée avec succès.',
            'intervention' => $intervention->fresh()->load('client'),
        ]);
    }

    /**
     * Supprimer une intervention appartenant à l'entreprise de l'utilisateur.
     */
    public function destroy(Request $request, int $id)
    {
        $intervention = Intervention::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->first();

        if (!$intervention) {
            return response()->json([
                'message' => 'Intervention introuvable.',
            ], 404);
        }

        $intervention->delete();

        return response()->json([
            'message' => 'Intervention supprimée avec succès.',
        ]);
    }
}