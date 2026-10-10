<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Création d'un compte ProElec
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
     * Connexion avec session Laravel + cookie Sanctum
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

        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            return response()->json([
                'message' => 'Adresse e-mail ou mot de passe incorrect.',
            ], 401);
        }

        // Empêche la réutilisation de l'ancien identifiant de session.
        $request->session()->regenerate();

        $user = $request->user();

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => $user,
            'entreprise' => $user->entreprise,
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}