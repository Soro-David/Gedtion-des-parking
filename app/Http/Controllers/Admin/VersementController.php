<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\User;
use App\Models\Versement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VersementController extends Controller
{
    public function index()
    {
        // Agents et caissiers ayant des sessions non versées OU une dette
        $agents = User::whereIn('role', ['attendant', 'caissier'])
            ->get()
            ->map(function (User $user) {
                // Sessions clôturées, non encore liées à un versement admin
                $sessions = ParkingSession::where('closed_by', $user->id)
                    ->where('status', 'released')
                    ->whereNull('versement_id')
                    ->get();

                $collectedAmount = $sessions->sum('amount');

                // Dette restante = solde du dernier versement uniquement
                $field = $user->role === 'caissier' ? 'caissier_id' : 'agent_id';
                $previousDebt = Versement::where($field, $user->id)->latest()->value('remaining_debt') ?? 0;

                $totalDue = $collectedAmount + $previousDebt;

                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'first_name'       => $user->first_name,
                    'email'            => $user->email,
                    'role'             => $user->role,
                    'collected_amount' => round($collectedAmount, 2),
                    'previous_debt'    => round($previousDebt, 2),
                    'total_due'        => round($totalDue, 2),
                    'sessions_count'   => $sessions->count(),
                ];
            })
            ->filter(fn($a) => $a['total_due'] > 0)
            ->values();

        // Historique des versements faits par cet admin
        $versements = Versement::with(['agent', 'caissier'])
            ->where('admin_id', auth()->id())
            ->latest()
            ->take(100)
            ->get()
            ->map(fn($v) => [
                'id'               => $v->id,
                'agent_name'       => ($v->agent ?? $v->caissier)->name ?? '—',
                'agent_role'       => ($v->agent ?? $v->caissier)->role ?? '—',
                'collected_amount' => $v->collected_amount,
                'previous_debt'    => $v->previous_debt,
                'total_due'        => $v->total_due,
                'paid_amount'      => $v->paid_amount,
                'remaining_debt'   => $v->remaining_debt,
                'note'             => $v->note,
                'created_at'       => $v->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Versement/Index', [
            'agents'     => $agents,
            'versements' => $versements,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'agent_id'    => 'required|exists:users,id',
            'paid_amount' => 'required|numeric|min:0',
            'note'        => 'nullable|string|max:500',
        ]);

        $agent = User::findOrFail($request->agent_id);

        $sessions = ParkingSession::where('closed_by', $agent->id)
            ->where('status', 'released')
            ->whereNull('versement_id')
            ->get();

        $collectedAmount = $sessions->sum('amount');
        $field           = $agent->role === 'caissier' ? 'caissier_id' : 'agent_id';
        $previousDebt    = Versement::where($field, $agent->id)->latest()->value('remaining_debt') ?? 0;
        $totalDue        = $collectedAmount + $previousDebt;
        $paidAmount      = (float) $request->paid_amount;
        $remainingDebt   = max(0, round($totalDue - $paidAmount, 2));

        $versement = Versement::create([
            'admin_id'         => auth()->id(),
            $field             => $agent->id,
            'collected_amount' => round($collectedAmount, 2),
            'previous_debt'    => round($previousDebt, 2),
            'total_due'        => round($totalDue, 2),
            'paid_amount'      => round($paidAmount, 2),
            'remaining_debt'   => $remainingDebt,
            'note'             => $request->note,
        ]);

        // Marquer les sessions comme versées
        if ($sessions->isNotEmpty()) {
            ParkingSession::whereIn('id', $sessions->pluck('id'))
                ->update(['versement_id' => $versement->id]);
        }

        $msg = 'Versement de ' . number_format($paidAmount, 0, ',', ' ') . ' FCFA enregistré.';
        if ($remainingDebt > 0) {
            $msg .= ' Dette restante : ' . number_format($remainingDebt, 0, ',', ' ') . ' FCFA.';
        }

        return back()->with('success', $msg);
    }
}
