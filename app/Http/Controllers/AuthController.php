<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Inscription d'un professionnel et création de son entreprise.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'entreprise_nom' => ['required', 'string', 'max:255'],
            'entreprise_email' => ['required', 'string', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['nullable', 'string', 'max:255'],
            'siret' => ['nullable', 'string', 'size:14'],
            'numero_tva' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = DB::transaction(function () use ($request) {
            $entreprise = Entreprise::create([
                'nom' => $request->entreprise_nom,
                'email' => $request->entreprise_email,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'code_postal' => $request->code_postal,
                'ville' => $request->ville,
                'siret' => $request->siret,
                'numero_tva' => $request->numero_tva,
            ]);

            $user = User::create([
                'name' => $request->nom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'entreprise_id' => $entreprise->id,
            ]);

            return [
                'entreprise' => $entreprise,
                'user' => $user,
            ];
        });

        return response()->json([
            'message' => 'Compte ProElec créé avec succès.',
            'user' => $result['user'],
            'entreprise' => $result['entreprise'],
        ], 201);
    }

    /**
     * Connexion d'un professionnel.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Adresse e-mail ou mot de passe incorrect.',
            ], 401);
        }

        $token = $user->createToken('proelec-api')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $token,
            'user' => $user,
            'entreprise' => $user->entreprise,
        ]);
    }
}