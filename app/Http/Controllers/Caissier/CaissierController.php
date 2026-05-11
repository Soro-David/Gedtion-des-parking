<?php

namespace App\Http\Controllers\Caissier;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\Reversement;
use App\Models\Versement;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class CaissierController extends Controller
{
    /**
     * Show caissier dashboard / landing page with stats.
     */
    public function index()
    {
        $user = auth()->user();
        $now  = Carbon::now();

        // Base query : sessions clôturées par ce caissier
        $base = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released');

        // Dette = sessions non encore collectées + dette résiduelle du dernier versement
        $pendingAmount  = (clone $base)
            ->whereNull('versement_id')
            ->whereNull('reversement_id')
            ->sum('amount');
        $previousDebt   = Versement::where('caissier_id', $user->id)->latest()->value('remaining_debt') ?? 0;
        $dette          = max(0, $pendingAmount + $previousDebt);

        // Montants encaissés
        $montantJour  = (clone $base)->whereDate('ended_at', $now->toDateString())->sum('amount');
        $montantMois  = (clone $base)->whereYear('ended_at', $now->year)->whereMonth('ended_at', $now->month)->sum('amount');
        $montantAnnee = (clone $base)->whereYear('ended_at', $now->year)->sum('amount');

        // Nombre de stationnements clôturés
        $nbJour  = (clone $base)->whereDate('ended_at', $now->toDateString())->count();
        $nbMois  = (clone $base)->whereYear('ended_at', $now->year)->whereMonth('ended_at', $now->month)->count();
        $nbAnnee = (clone $base)->whereYear('ended_at', $now->year)->count();

        // Derniers versements reçus par ce caissier (admin → caissier)
        $dernierVersements = Versement::with('admin')
            ->where('caissier_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($v) => [
                'id'             => $v->id,
                'paid_amount'    => $v->paid_amount,
                'remaining_debt' => $v->remaining_debt,
                'admin_name'     => $v->admin->name ?? '—',
                'created_at'     => $v->created_at->toDateTimeString(),
            ]);

        // Dernières sorties encaissées par ce caissier
        $derniersSessions = ParkingSession::with('parking')
            ->where('closed_by', $user->id)
            ->where('status', 'released')
            ->latest('ended_at')
            ->take(5)
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'license_plate' => $s->license_plate,
                'marque'        => $s->marque,
                'modele'        => $s->modele,
                'parking_name'  => $s->parking?->name,
                'ended_at'      => $s->ended_at?->toDateTimeString(),
                'amount'        => $s->amount,
            ]);

        return Inertia::render('Caissier/Index', [
            'dette'             => $dette,
            'montantJour'       => $montantJour,
            'montantMois'       => $montantMois,
            'montantAnnee'      => $montantAnnee,
            'nbJour'            => $nbJour,
            'nbMois'            => $nbMois,
            'nbAnnee'           => $nbAnnee,
            'dernierVersements' => $dernierVersements,
            'derniersSessions'  => $derniersSessions,
        ]);
    }
}
