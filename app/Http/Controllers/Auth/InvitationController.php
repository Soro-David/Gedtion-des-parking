<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Show the set-password form for the given invitation token.
     */
    public function show(string $token)
    {
        $user = User::where('invitation_token', $token)->first();

        if (! $user || ! $user->invitation_expires_at || $user->invitation_expires_at->isPast()) {
            return Inertia::render('Auth/InvitationExpired');
        }

        return Inertia::render('Auth/SetPassword', [
            'token' => $token,
            'email' => $user->email,
            'name'  => $user->name . ' ' . $user->first_name,
        ]);
    }

    /**
     * Save the new password and activate the account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'avatar'                => 'nullable|image|max:5120',
        ]);

        $user = User::where('invitation_token', $request->token)->first();

        if (! $user || ! $user->invitation_expires_at || $user->invitation_expires_at->isPast()) {
            return back()->withErrors(['token' => 'Ce lien d\'invitation est invalide ou a expiré.']);
        }

        $updateData = [
            'password'               => Hash::make($request->password),
            'invitation_token'       => null,
            'invitation_expires_at'  => null,
            'email_verified_at'      => now(),
        ];

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $avatarPath;
        }

        $user->update($updateData);

        return redirect()->route('login')->with('status', 'Mot de passe défini avec succès. Vous pouvez maintenant vous connecter.');
    }
}
