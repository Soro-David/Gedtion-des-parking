<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Reversement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReversementController extends Controller
{
    /**
     * Liste de tous les reversements reçus par ce superviseur.
     */
    public function index()
    {
        $reversements = Reversement::with(['user', 'receiver'])
            ->where('receiver_id', auth()->id())
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'amount'         => $r->amount,
                'note'           => $r->note,
                'status'         => $r->status,
                'confirmed_at'   => $r->confirmed_at?->toDateTimeString(),
                'created_at'     => $r->created_at->toDateTimeString(),
                'agent_name'     => $r->user->name ?? '—',
                'agent_role'     => $r->user->role ?? '—',
                'sessions_count' => $r->sessions()->count(),
            ]);

        $totalPending   = $reversements->where('status', 'pending')->sum('amount');
        $totalConfirmed = $reversements->where('status', 'confirmed')->sum('amount');

        return Inertia::render('Supervisor/Reversement/Index', [
            'reversements'   => $reversements,
            'totalPending'   => $totalPending,
            'totalConfirmed' => $totalConfirmed,
        ]);
    }

    /**
     * Confirmer un reversement.
     */
    public function confirm(Reversement $reversement)
    {
        if ($reversement->receiver_id !== auth()->id()) {
            abort(403);
        }

        if ($reversement->status === 'confirmed') {
            return back()->withErrors(['general' => 'Ce reversement est déjà confirmé.']);
        }

        $reversement->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Reversement confirmé avec succès.');
    }
}
