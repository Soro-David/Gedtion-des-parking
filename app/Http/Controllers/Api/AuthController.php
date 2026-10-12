<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    /**
     * Connexion pour les agents (attendants) et caissiers.
     * Le champ "login" accepte un email ou un numéro de téléphone.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'     => ['required', 'string'],
            'password'  => ['required', 'string'],
            'fcm_token' => ['nullable', 'string'],
        ]);

        $login = $request->input('login');

        // Détecte si la valeur saisie est un email ou un téléphone
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::query()->where($field, $login)->first();

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

        // Enregistrer le token FCM s'il est fourni
        if ($request->filled('fcm_token')) {
            $user->updateFcmToken($request->fcm_token);
        }

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
                'address'    => $user->address,
                'avatar_url' => $user->avatar_url,
                'parkings'   => $user->parkings->map(fn($p) => [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'reference'       => $p->reference,
                    'capacity'        => $p->capacity,
                    'available_spots' => $p->available_spots,
                ]),
            ],
        ]);
    }

    /**
     * Déconnexion : révoque le token courant.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->clearFcmToken();
        }

        $token = $request->user()->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

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
                'address'    => $user->address,
                'role'       => $user->role,
                'avatar_url' => $user->avatar_url,
                'parkings'   => $user->parkings->map(fn($p) => [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'reference'       => $p->reference,
                    'capacity'        => $p->capacity,
                    'available_spots' => $p->available_spots,
                ]),
            ],
            'profile' => $profile,
        ]);
    }

    /**
     * Modification du profil de l'utilisateur connecté.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'email'      => ['sometimes', 'required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'      => ['nullable', 'string', 'max:50'],
            'address'    => ['nullable', 'string', 'max:255'],
            'password'   => ['nullable', 'string', 'min:8'],
            'avatar'     => ['nullable', 'image', 'max:5120'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar s'il existe
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $user->update($validated);

        $profile = match ($user->role) {
            User::ROLE_ATTENDANT => $user->attendant,
            User::ROLE_CAISSIER  => $user->caissier,
            default              => null,
        };

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'first_name' => $user->first_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'address'    => $user->address,
                'role'       => $user->role,
                'avatar_url' => $user->avatar_url,
                'parkings'   => $user->parkings->map(fn($p) => [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'reference'       => $p->reference,
                    'capacity'        => $p->capacity,
                    'available_spots' => $p->available_spots,
                ]),
            ],
            'profile' => $profile,
        ]);
    }

    /**
     * Modification du mot de passe de l'utilisateur connecté.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }


    /**
     * Demande d'un lien de réinitialisation de mot de passe (mot de passe oublié).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Un e-mail de réinitialisation de mot de passe a été envoyé.'
            ]);
        }

        return response()->json([
            'message' => 'Impossible d\'envoyer l\'e-mail de réinitialisation.'
        ], 400);
    }

    /**
     * Réinitialisation effective du mot de passe avec le token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Révocation de tous les jetons actifs de l'utilisateur pour forcer la reconnexion
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Votre mot de passe a été réinitialisé avec succès.'
            ]);
        }

        return response()->json([
            'message' => 'Ce token de réinitialisation de mot de passe est invalide ou expiré.'
        ], 400);
    }

    /**
     * Envoi (ou renvoi) de l'e-mail de vérification.
     */
    public function sendEmailVerification(Request $request)
    {
        $user = null;

        if ($request->user('sanctum')) {
            $user = $request->user('sanctum');
        } else {
            $request->validate([
                'email' => ['required', 'email', 'exists:users,email'],
            ]);
            $user = User::where('email', $request->email)->first();
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Cet e-mail est déjà vérifié.'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Lien de vérification envoyé sur votre e-mail.'
        ]);
    }

    /**
     * Confirmation de la vérification de l'adresse e-mail.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        // Validation de la signature temporaire de l'URL pour la sécurité
        if (! $request->hasValidSignature()) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Lien de vérification invalide ou expiré.'
                ], 403);
            }
            return $this->renderErrorHtml('Lien de vérification invalide ou expiré.');
        }

        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Lien de vérification invalide.'
                ], 403);
            }
            return $this->renderErrorHtml('Le lien de vérification ne correspond pas à cet utilisateur.');
        }

        if ($user->hasVerifiedEmail()) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Adresse e-mail déjà vérifiée.'
                ]);
            }
            return $this->renderSuccessHtml('Votre adresse e-mail a déjà été vérifiée.');
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Votre adresse e-mail a été vérifiée avec succès.'
            ]);
        }

        return $this->renderSuccessHtml('Votre adresse e-mail a été vérifiée avec succès.');
    }

    /**
     * Rendu HTML premium d'une page de succès pour la vérification d'email.
     */
    private function renderSuccessHtml(string $message)
    {
        return response(<<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Vérifié - Parking App</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 90%;
            animation: fadeIn 0.8s ease-out;
        }
        .icon {
            font-size: 5rem;
            color: #10b981;
            margin-bottom: 1.5rem;
            animation: scaleIn 0.5s ease-out;
        }
        h1 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #10b981, #059669);
            color: white;
            text-decoration: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✓</div>
        <h1>Email Vérifié !</h1>
        <p>{$message}</p>
        <button class="btn" onclick="window.close()">Fermer la page</button>
    </div>
</body>
</html>
HTML
        )->header('Content-Type', 'text/html');
    }

    /**
     * Rendu HTML premium d'une page d'erreur pour la vérification d'email.
     */
    private function renderErrorHtml(string $message)
    {
        return response(<<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de Vérification - Parking App</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 90%;
            animation: fadeIn 0.8s ease-out;
        }
        .icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
            animation: scaleIn 0.5s ease-out;
        }
        h1 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #ef4444, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #ef4444, #dc2626);
            color: white;
            text-decoration: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✕</div>
        <h1>Lien Invalide</h1>
        <p>{$message}</p>
        <button class="btn" onclick="window.close()">Fermer la page</button>
    </div>
</body>
</html>
HTML
        )->header('Content-Type', 'text/html');
    }
}

