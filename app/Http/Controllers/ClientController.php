<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Lister les clients de l'entreprise de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $clients = Client::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->orderBy('nom')
        ->orderBy('prenom')
        ->get();

        return response()->json([
            'clients' => $clients,
        ]);
    }

    /**
     * Créer un nouveau client pour l'entreprise de l'utilisateur connecté.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $client = Client::create([
            'entreprise_id' => $request->user()->entreprise_id,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'code_postal' => $request->code_postal,
            'ville' => $request->ville,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Client créé avec succès.',
            'client' => $client,
        ], 201);
    }

    /**
     * Modifier un client appartenant à l'entreprise de l'utilisateur connecté.
     */
    public function update(Request $request, int $id)
    {
        $client = Client::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Client introuvable.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'prenom' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'adresse' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code_postal' => ['sometimes', 'nullable', 'string', 'max:10'],
            'ville' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $client->update($request->only([
            'nom',
            'prenom',
            'email',
            'telephone',
            'adresse',
            'code_postal',
            'ville',
            'notes',
        ]));

        return response()->json([
            'message' => 'Client modifié avec succès.',
            'client' => $client->fresh(),
        ]);
    }

    /**
     * Supprimer un client appartenant à l'entreprise de l'utilisateur connecté.
     */
    public function destroy(Request $request, int $id)
    {
        $client = Client::where(
            'entreprise_id',
            $request->user()->entreprise_id
        )
        ->where('id', $id)
        ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Client introuvable.',
            ], 404);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client supprimé avec succès.',
        ]);
    }
}