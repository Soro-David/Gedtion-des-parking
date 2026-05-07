<?php

namespace App\Http\Controllers\Attendant;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\Reversement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReversementController extends Controller
{
    /**
     * Liste des reversements + solde en attente pour l'agent.
     */
    public function index()
    {
        $user = auth()->user();

        // Sessions clôturées par cet agent non encore reversées
        $pendingSessions = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released')
            ->whereNull('reversement_id')
            ->get();

        $pendingAmount = $pendingSessions->sum('amount');

        // Historique des reversements de l'agent
        $reversements = Reversement::with('receiver')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'           => $r->id,
                'amount'       => $r->amount,
                'note'         => $r->note,
                'status'       => $r->status,
                'confirmed_at' => $r->confirmed_at?->toDateTimeString(),
                'created_at'   => $r->created_at->toDateTimeString(),
                'receiver'     => $r->receiver->name ?? '—',
            ]);

        // Admins et superviseurs disponibles comme récepteurs
        $receivers = User::whereIn('role', ['admin', 'supervisor'])
            ->select('id', 'name', 'email', 'role')
            ->get();

        return Inertia::render('Attendant/Reversement/Index', [
            'pendingAmount'   => $pendingAmount,
            'pendingSessions' => $pendingSessions->count(),
            'reversements'    => $reversements,
            'receivers'       => $receivers,
        ]);
    }

    /**
     * Créer un reversement avec toutes les sessions en attente.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'note'        => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        $sessions = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released')
            ->whereNull('reversement_id')
            ->get();

        if ($sessions->isEmpty()) {
            return back()->withErrors(['general' => 'Aucun montant à reverser.']);
        }

        $amount = $sessions->sum('amount');

        $reversement = Reversement::create([
            'user_id'     => $user->id,
            'receiver_id' => $request->receiver_id,
            'amount'      => $amount,
            'note'        => $request->note,
            'status'      => 'pending',
        ]);

        // Lier les sessions à ce reversement
        ParkingSession::whereIn('id', $sessions->pluck('id'))
            ->update(['reversement_id' => $reversement->id]);

        return back()->with('success', 'Reversement de ' . number_format($amount, 0, ',', ' ') . ' FCFA soumis avec succès.');
    }
}
