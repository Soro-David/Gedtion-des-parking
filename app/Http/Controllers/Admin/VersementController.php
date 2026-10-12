<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\ParkingSession;
use App\Models\User;
use App\Models\Versement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class VersementController extends Controller
{
    public function index()
    {
        // Récupérer tous les parkings actifs
        $parkings = Parking::where('is_active', true)->get();
        $items = collect();

        foreach ($parkings as $parking) {
            // Vérifier s'il y a un caissier actif assigné à ce parking
            $activeCashier = $parking->users()
                ->where('role', User::ROLE_CAISSIER)
                ->first();

            if ($activeCashier) {
                // CAS 1 : Le parking possède un caissier actif
                // Le montant total à verser = somme de toutes les sorties payées non encore versées du parking (tous agents/caissiers)
                $sessions = ParkingSession::where('parking_id', $parking->id)
                    ->where('status', 'released')
                    ->whereNull('versement_id')
                    ->get();

                $collectedAmount = (float) $sessions->sum('amount');
                $previousDebt    = (float) (Versement::where('caissier_id', $activeCashier->id)->latest()->value('remaining_debt') ?? 0);
                $totalDue        = $collectedAmount + $previousDebt;

                // Date de dernière activité sur le parking
                $lastActivity = ParkingSession::where('parking_id', $parking->id)
                    ->latest('ended_at')
                    ->value('ended_at') ?? ParkingSession::where('parking_id', $parking->id)->latest('started_at')->value('started_at');

                if ($totalDue > 0 || $sessions->isNotEmpty()) {
                    $items->push([
                        'id'                => 'parking_' . $parking->id,
                        'parking_id'        => $parking->id,
                        'parking_name'      => $parking->name,
                        'type'              => 'caissier',
                        'caissier_id'       => $activeCashier->id,
                        'agent_id'          => null,
                        'responsible_name'  => trim($activeCashier->name . ' ' . ($activeCashier->first_name ?? '')),
                        'responsible_role'  => 'caissier',
                        'responsible_email' => $activeCashier->email,
                        'collected_amount'  => round($collectedAmount, 2),
                        'previous_debt'     => round($previousDebt, 2),
                        'total_due'         => round($totalDue, 2),
                        'sessions_count'    => $sessions->count(),
                        'last_activity'     => $lastActivity ? Carbon::parse($lastActivity)->format('d/m/Y H:i') : '—',
                    ]);
                }
            } else {
                // CAS 2 : Aucun caissier actif dans ce parking -> versement rattaché aux agents
                $agents = $parking->users()
                    ->where('role', User::ROLE_ATTENDANT)
                    ->get();

                $attendantsWhoOperated = ParkingSession::where('parking_id', $parking->id)
                    ->where('status', 'released')
                    ->whereNull('versement_id')
                    ->pluck('closed_by')
                    ->filter();

                $allAgentIds = $agents->pluck('id')->merge($attendantsWhoOperated)->unique();

                foreach ($allAgentIds as $agentId) {
                    $agent = User::find($agentId);
                    if (!$agent) continue;

                    $sessions = ParkingSession::where('parking_id', $parking->id)
                        ->where('closed_by', $agent->id)
                        ->where('status', 'released')
                        ->whereNull('versement_id')
                        ->get();

                    $collectedAmount = (float) $sessions->sum('amount');
                    $previousDebt    = (float) (Versement::where('agent_id', $agent->id)->latest()->value('remaining_debt') ?? 0);
                    $totalDue        = $collectedAmount + $previousDebt;

                    $lastActivity = ParkingSession::where('parking_id', $parking->id)
                        ->where('closed_by', $agent->id)
                        ->latest('ended_at')
                        ->value('ended_at');

                    if ($totalDue > 0 || $sessions->isNotEmpty()) {
                        $items->push([
                            'id'                => 'parking_' . $parking->id . '_agent_' . $agent->id,
                            'parking_id'        => $parking->id,
                            'parking_name'      => $parking->name,
                            'type'              => 'agent',
                            'caissier_id'       => null,
                            'agent_id'          => $agent->id,
                            'responsible_name'  => trim($agent->name . ' ' . ($agent->first_name ?? '')),
                            'responsible_role'  => 'attendant',
                            'responsible_email' => $agent->email,
                            'collected_amount'  => round($collectedAmount, 2),
                            'previous_debt'     => round($previousDebt, 2),
                            'total_due'         => round($totalDue, 2),
                            'sessions_count'    => $sessions->count(),
                            'last_activity'     => $lastActivity ? Carbon::parse($lastActivity)->format('d/m/Y H:i') : '—',
                        ]);
                    }
                }
            }
        }

        // Historique des versements faits par cet admin
        $versements = Versement::with(['agent', 'caissier', 'sessions.parking'])
            ->where('admin_id', auth()->id())
            ->latest()
            ->take(100)
            ->get()
            ->map(function ($v) {
                $person = $v->caissier ?? $v->agent;
                $parkingName = $v->sessions->first()?->parking?->name ?? '—';

                return [
                    'id'               => $v->id,
                    'parking_name'     => $parkingName,
                    'responsible_name' => $person ? trim($person->name . ' ' . ($person->first_name ?? '')) : '—',
                    'responsible_role' => $person?->role ?? '—',
                    'collected_amount' => (float) $v->collected_amount,
                    'previous_debt'    => (float) $v->previous_debt,
                    'total_due'        => (float) $v->total_due,
                    'paid_amount'      => (float) $v->paid_amount,
                    'remaining_debt'   => (float) $v->remaining_debt,
                    'note'             => $v->note,
                    'created_at'       => $v->created_at->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/Versement/Index', [
            'items'      => $items->values(),
            'versements' => $versements,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'parking_id'  => 'nullable|exists:parkings,id',
            'caissier_id' => 'nullable|exists:users,id',
            'agent_id'    => 'nullable|exists:users,id',
            'paid_amount' => 'required|numeric|min:0',
            'note'        => 'nullable|string|max:500',
        ]);

        if (!$request->filled('caissier_id') && !$request->filled('agent_id')) {
            return back()->withErrors(['paid_amount' => 'Responsable du versement non identifié.']);
        }

        $paidAmount = (float) $request->paid_amount;

        if ($request->filled('caissier_id') && $request->filled('parking_id')) {
            $caissier = User::findOrFail($request->caissier_id);
            $parkingId = $request->parking_id;

            // Sessions clôturées du parking non encore versées (tous agents/caissiers)
            $sessions = ParkingSession::where('parking_id', $parkingId)
                ->where('status', 'released')
                ->whereNull('versement_id')
                ->get();

            $collectedAmount = (float) $sessions->sum('amount');
            $previousDebt    = (float) (Versement::where('caissier_id', $caissier->id)->latest()->value('remaining_debt') ?? 0);
            $totalDue        = $collectedAmount + $previousDebt;
            $remainingDebt   = max(0, round($totalDue - $paidAmount, 2));

            $versement = Versement::create([
                'admin_id'         => auth()->id(),
                'caissier_id'      => $caissier->id,
                'agent_id'         => null,
                'collected_amount' => round($collectedAmount, 2),
                'previous_debt'    => round($previousDebt, 2),
                'total_due'        => round($totalDue, 2),
                'paid_amount'      => round($paidAmount, 2),
                'remaining_debt'   => $remainingDebt,
                'note'             => $request->note,
            ]);

            if ($sessions->isNotEmpty()) {
                ParkingSession::whereIn('id', $sessions->pluck('id'))
                    ->update(['versement_id' => $versement->id]);
            }
        } else {
            $agent = User::findOrFail($request->agent_id);
            $query = ParkingSession::where('closed_by', $agent->id)
                ->where('status', 'released')
                ->whereNull('versement_id');

            if ($request->filled('parking_id')) {
                $query->where('parking_id', $request->parking_id);
            }

            $sessions = $query->get();

            $collectedAmount = (float) $sessions->sum('amount');
            $previousDebt    = (float) (Versement::where('agent_id', $agent->id)->latest()->value('remaining_debt') ?? 0);
            $totalDue        = $collectedAmount + $previousDebt;
            $remainingDebt   = max(0, round($totalDue - $paidAmount, 2));

            $versement = Versement::create([
                'admin_id'         => auth()->id(),
                'agent_id'         => $agent->id,
                'caissier_id'      => null,
                'collected_amount' => round($collectedAmount, 2),
                'previous_debt'    => round($previousDebt, 2),
                'total_due'        => round($totalDue, 2),
                'paid_amount'      => round($paidAmount, 2),
                'remaining_debt'   => $remainingDebt,
                'note'             => $request->note,
            ]);

            if ($sessions->isNotEmpty()) {
                ParkingSession::whereIn('id', $sessions->pluck('id'))
                    ->update(['versement_id' => $versement->id]);
            }
        }

        $msg = 'Versement de ' . number_format($paidAmount, 0, ',', ' ') . ' FCFA enregistré.';
        if ($remainingDebt > 0) {
            $msg .= ' Dette restante : ' . number_format($remainingDebt, 0, ',', ' ') . ' FCFA.';
        }

        return back()->with('success', $msg);
    }
}
