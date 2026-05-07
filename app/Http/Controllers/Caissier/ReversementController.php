<?php

namespace App\Http\Controllers\Caissier;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\Reversement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReversementController extends Controller
{
    /**
     * Liste des reversements + solde en attente pour le caissier.
     */
    public function index()
    {
        $user = auth()->user();

        // Historique des reversements du caissier
        $reversements = Reversement::with(['receiver', 'sessions'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'amount'         => $r->amount,
                'montant_percu'  => $r->sessions->sum('amount'),
                'sessions_count' => $r->sessions->count(),
                'reste'          => $r->status === 'confirmed' ? 0 : $r->amount,
                'note'           => $r->note,
                'status'         => $r->status,
                'confirmed_at'   => $r->confirmed_at?->toDateTimeString(),
                'created_at'     => $r->created_at->toDateTimeString(),
                'receiver'       => $r->receiver->name ?? '—',
            ]);

        return Inertia::render('Caissier/Reversement/Index', [
            'reversements' => $reversements,
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
