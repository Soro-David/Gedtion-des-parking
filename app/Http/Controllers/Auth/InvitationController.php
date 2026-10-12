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

        $email = $user->email;
        $name = trim($user->name . ' ' . $user->first_name);
        $role = $user->role;

        $user->update($updateData);

        return redirect()->route('invitation.success', [
            'email' => $email,
            'name'  => $name,
            'role'  => $role,
        ]);
    }

    /**
     * Show confirmation page and trigger deep link to mobile app.
     */
    public function success(Request $request)
    {
        $email = $request->query('email', '');
        $name  = $request->query('name', '');
        $role  = $request->query('role', '');

        $deepLinkUrl = 'parkingapp://login?email=' . urlencode($email);

        return Inertia::render('Auth/InvitationSuccess', [
            'email'       => $email,
            'name'        => $name,
            'role'        => $role,
            'deepLinkUrl' => $deepLinkUrl,
            'apkUrl'      => config('app.mobile_apk_url', ''),
        ]);
    }
}
