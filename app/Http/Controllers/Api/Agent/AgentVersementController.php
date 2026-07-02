<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\User;
use App\Models\Versement;
use App\Services\FcmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentVersementController extends Controller
{
    public function list(Request $request)
    {
        $versements = Versement::where(['agent_id' => $request->user()->id])
            ->latest('created_at')
            ->get();
        return response()->json([
            'versements' => $versements,
        ]);
    }

    public function show(Request $request, $id)
    {
        $versement = Versement::where(['agent_id' => $request->user()->id])->findOrFail($id);
        return response()->json([
            'data' => $versement,
        ]);
    }

    /**
     * Créer un versement (l'agent verse au caissier).
     * La somme est calculée automatiquement à partir des sessions non versées.
     */
    public function store(Request $request)
    {
        $request->validate([
            'caissier_id' => 'required|exists:users,id',
            'paid_amount' => 'required|numeric|min:0',
            'note'        => 'nullable|string|max:500',
        ]);

        $agent    = auth()->user();
        $caissier = User::where('id', $request->caissier_id)
            ->where('role', 'caissier')
            ->firstOrFail();

        // Sessions non encore versées, clôturées par cet agent
        $sessions = ParkingSession::where('closed_by', $agent->id)
            ->where('status', 'released')
            ->whereNull('versement_id')
            ->get();

        $collectedAmount = $sessions->sum('amount');
        $previousDebt    = Versement::where('agent_id', $agent->id)->latest()->value('remaining_debt') ?? 0;
        $totalDue        = $collectedAmount + $previousDebt;
        $paidAmount      = (float) $request->paid_amount;
        $remainingDebt   = max(0, round($totalDue - $paidAmount, 2));

        $versement = Versement::create([
            'admin_id'         => $caissier->id,   // le caissier reçoit le versement
            'agent_id'         => $agent->id,
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

        // ✓ Notification FCM : informer le caissier du versement reçu
        try {
            /*
            app(FcmNotificationService::class)->sendToUser(
                $caissier,
                'Versement reçu 💰',
                "Agent {$agent->name} vous a versé " . number_format($paidAmount, 0, ',', ' ') . ' FCFA'
                . ($remainingDebt > 0 ? " (dette restante : " . number_format($remainingDebt, 0, ',', ' ') . " FCFA)" : ''),
                [
                    'type'         => 'versement',
                    'id'           => (string) $versement->id,
                    'paid_amount'  => (string) $paidAmount,
                    'agent_name'   => $agent->name,
                ],
                'versement'
            );
            */
        } catch (\Exception $e) {
            Log::warning('[FCM Agent] Erreur notification versement caissier', ['error' => $e->getMessage()]);
        }

        $msg = 'Versement de ' . number_format($paidAmount, 0, ',', ' ') . ' FCFA enregistré.';
        if ($remainingDebt > 0) {
            $msg .= ' Dette restante : ' . number_format($remainingDebt, 0, ',', ' ') . ' FCFA.';
        }

        return response()->json([
            'message'   => $msg,
            'versement' => [
                'id'               => $versement->id,
                'collected_amount' => $versement->collected_amount,
                'previous_debt'    => $versement->previous_debt,
                'total_due'        => $versement->total_due,
                'paid_amount'      => $versement->paid_amount,
                'remaining_debt'   => $versement->remaining_debt,
                'note'             => $versement->note,
                'created_at'       => $versement->created_at->toDateTimeString(),
            ],
        ], 201);
    }
}

