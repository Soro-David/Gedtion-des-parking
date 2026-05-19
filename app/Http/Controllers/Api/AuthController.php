<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion pour les agents (attendants) et caissiers.
     * Le champ "login" accepte un email ou un numéro de téléphone.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $request->input('login');

        // Détecte si la valeur saisie est un email ou un téléphone
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Identifiants incorrects.'],
            ]);
        }

        // Seuls les agents (attendant) et caissiers peuvent utiliser cette API
        if (! in_array($user->role, [User::ROLE_ATTENDANT, User::ROLE_CAISSIER])) {
            throw ValidationException::withMessages([
                'login' => ['Accès non autorisé pour ce rôle.'],
            ]);
        }

        // Révoque tous les tokens existants pour éviter les doublons
        $user->tokens()->delete();

        $tokenName = match ($user->role) {
            User::ROLE_ATTENDANT => 'agent-token',
            User::ROLE_CAISSIER  => 'caissier-token',
        };

        $token = $user->createToken($tokenName, [$user->role])->plainTextToken;

        return response()->json([
            'token'  => $token,
            'role'   => $user->role,
            'user'   => [
                'id'         => $user->id,
                'name'       => $user->name,
                'first_name' => $user->first_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
            ],
        ]);
    }

    /**
     * Déconnexion : révoque le token courant.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Retourne l'utilisateur authentifié avec son profil.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $profile = match ($user->role) {
            User::ROLE_ATTENDANT => $user->attendant,
            User::ROLE_CAISSIER  => $user->caissier,
            default              => null,
        };

        return response()->json([
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'first_name' => $user->first_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
            ],
            'profile' => $profile,
        ]);
    }
}

