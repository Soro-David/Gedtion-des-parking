<?php

namespace App\Http\Controllers\Api\Caissier;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\User;
use App\Models\Versement;
use App\Services\FcmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaissierVersementController extends Controller
{
    public function list(Request $request)
    {
        $versements = Versement::where('caissier_id', $request->user()->id)->get();
        return response()->json([
            'versements' => $versements,
        ]);
    }

    public function show(Request $request, $id)
    {
        $versement = Versement::where('caissier_id', $request->user()->id)->findOrFail($id);
        return response()->json([
            'data' => $versement,
        ]);
    }

    /**
     * Enregistrer un versement reçu d'un agent (le caissier valide la réception).
     * La somme collectée est calculée automatiquement à partir des sessions non versées de l'agent.
     */
    public function store(Request $request)
    {
        $request->validate([
            'agent_id'    => 'required|exists:users,id',
            'paid_amount' => 'required|numeric|min:0',
            'note'        => 'nullable|string|max:500',
        ]);

        $caissier = auth()->user();
        $agent    = User::where('id', $request->agent_id)
            ->where('role', 'attendant')
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
            'admin_id'         => $caissier->id,   // le caissier enregistre la réception
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

        // ✓ Notification FCM : informer l'agent que son versement a été enregistré
        try {
            /*
            app(FcmNotificationService::class)->sendToUser(
                $agent,
                'Versement enregistré',
                "Cher {$agent->name}, votre versement d'un montant de " . number_format($paidAmount, 0, ',', ' ') . " FCFA a été enregistré avec succès.",
                [
                    'type'          => 'versement',
                    'id'            => (string) $versement->id,
                    'paid_amount'   => (string) $paidAmount,
                    'caissier_name' => $caissier->name,
                ],
                'versement'
            );
            */
        } catch (\Exception $e) {
            Log::warning('[FCM Caissier] Erreur notification versement agent', ['error' => $e->getMessage()]);
        }

        $msg = 'Versement de ' . number_format($paidAmount, 0, ',', ' ') . ' FCFA enregistré.';
        if ($remainingDebt > 0) {
            $msg .= ' Dette restante : ' . number_format($remainingDebt, 0, ',', ' ') . ' FCFA.';
        }

        return response()->json([
            'message'   => $msg,
            'versement' => [
                'id'               => $versement->id,
                'agent_name'       => $agent->name,
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

