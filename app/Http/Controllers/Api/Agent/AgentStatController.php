<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParkingSession;
use App\Models\Versement;

class AgentStatController extends Controller
{
    public function total_encaisser(Request $request)
    {
        $user = $request->user();
        $total = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released')
            ->whereDate('ended_at', now()->toDateString())
            ->sum('amount');

        return response()->json([
            'total_encaisser' => round($total, 2),
        ]);
    }

    public function encaisse_non_verse(Request $request)
    {
        $user = $request->user();
        $nonVerse = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released')
            ->whereNull('versement_id')
            ->whereNull('reversement_id')
            ->sum('amount');

        return response()->json([
            'encaisse_non_verse' => round($nonVerse, 2),
        ]);
    }

    public function dette(Request $request)
    {
        $user = $request->user();
        
        $pendingAmount = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released')
            ->whereNull('versement_id')
            ->whereNull('reversement_id')
            ->sum('amount');

        $previousDebt = Versement::where('agent_id', $user->id)
            ->latest()
            ->value('remaining_debt') ?? 0;

        $detteRestante = max(0, $pendingAmount + $previousDebt);

        return response()->json([
            'dette' => round($detteRestante, 2),
        ]);
    }
    
    public function stationnement_en_cours(Request $request)
    {
        $user = $request->user();

        // Compter TOUS les véhicules occupés dans les parkings affectés à cet agent
        $parkingIds = $user->parkings()->pluck('parkings.id');
        $count = ParkingSession::whereIn('parking_id', $parkingIds)
            ->where('status', 'occupied')
            ->count();

        return response()->json([
            'stationnement_en_cours' => $count,
        ]);
    }
}
