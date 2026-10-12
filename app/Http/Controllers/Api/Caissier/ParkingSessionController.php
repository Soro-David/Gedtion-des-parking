<?php

namespace App\Http\Controllers\Api\Caissier;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\ParkingRate;
use App\Models\ParkingSession;
use App\Models\Versement;
use App\Services\FcmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;


class ParkingSessionController extends Controller
{
    /**
     * Enregistrer une nouvelle entrée de véhicule (Check-in).
     * Les caissiers ne sont pas autorisés à enregistrer les entrées de véhicules.
     */
    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Les caissiers ne sont pas autorisés à enregistrer les entrées de véhicules. Cette action est exclusivement réservée aux agents de parking.'
        ], 403);
    }

    /**
     * Enregistrer la sortie d'un véhicule (Check-out).
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|exists:parking_sessions,id',
            'payment_method' => 'nullable|string|max:50',
            'amount'         => 'nullable|numeric|min:0',
        ]);

        $session = ParkingSession::where('id', $request->session_id)
            ->where('status', 'occupied')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Cette session est introuvable ou déjà clôturée.'
            ], 404);
        }

        $user = auth()->user();
        $endedAt = now();
        $minutes = (int) ceil($session->started_at->diffInMinutes($endedAt));
        $amount  = $request->filled('amount') 
            ? $request->amount 
            : ParkingRate::calculateAmount($endedAt->format('H:i'), $session->parking_id);

        $session->update([
            'status'         => 'released',
            'ended_at'       => $endedAt,
            'amount'         => $amount,
            'payment_method' => $request->payment_method,
            'closed_by'      => $user->id,
        ]);

        Log::info('API Caissier - Sortie de véhicule enregistrée', [
            'session_id'    => $session->id,
            'license_plate' => $session->license_plate,
            'minutes'       => $minutes,
            'amount'        => $amount,
            'caissier_id'   => $user->id,
        ]);

        // ✓ Notification FCM : informer tous les agents et caissiers du même parking de la sortie
        try {
            $parking = $session->parking;
            if ($parking) {
                $recipients = $parking->users()
                    ->whereIn('role', ['attendant', 'caissier'])
                    ->where('users.id', '!=', $user->id)
                    ->pluck('users.id')
                    ->toArray();

                if (!empty($recipients)) {
                    app(FcmNotificationService::class)->sendToUsers(
                        $recipients,
                        'Sortie de véhicule enregistrée',
                        "Un véhicule vient de sortir du parking {$parking->name}. La sortie a été enregistrée par {$user->name}.",
                        [
                            'type'       => 'sortie',
                            'id'         => (string) $session->id,
                            'parking_id' => (string) $parking->id,
                        ],
                        'sortie'
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('[FCM Caissier] Erreur notification sortie collègues', ['error' => $e->getMessage()]);
        }

        return response()->json([

            'message' => 'Sortie enregistrée avec succès.',
            'receipt' => [
                'id'               => $session->id,
                'license_plate'    => $session->license_plate,
                'marque'           => $session->marque,
                'modele'           => $session->modele,
                'parking_name'     => $session->parking?->name,
                'parking_ref'      => $session->parking?->reference,
                'started_at'       => $session->started_at->toDateTimeString(),
                'ended_at'         => $session->ended_at->toDateTimeString(),
                'duration_minutes' => $minutes,
                'amount'           => $session->amount,
                'agent_name'       => $user->name,
            ]
        ]);
    }

    /**
     * Historique des entrées que ce caissier a enregistrées (peu importe qui a fait la sortie).
     */
    public function historyEntries(Request $request)
    {
        $user = auth()->user();

        $entries = ParkingSession::with('parking')
            ->where('created_by', $user->id)
            ->where('status', 'released')
            ->latest('started_at')
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'license_plate'  => $s->license_plate,
                'marque'         => $s->marque,
                'modele'         => $s->modele,
                'parking_name'   => $s->parking?->name,
                'started_at'     => $s->started_at->toDateTimeString(),
                'ended_at'       => $s->ended_at?->toDateTimeString(),
                'amount'         => $s->amount,
                'payment_method' => $s->payment_method,
                'status'         => $s->status,
            ]);

        return response()->json([
            'message' => 'Historique des entrées récupéré avec succès.',
            'entries' => $entries,
        ]);
    }

    /**
     * Stationnements en cours : TOUS les véhicules actuellement occupés dans les parkings
     * affectés à ce caissier (peu importe qui les a enregistrés).
     */
    public function stationnement_en_cours(Request $request)
    {
        $user = auth()->user();

        // Récupérer les parkings affectés à ce caissier
        $parkingIds = $user->parkings()->pluck('parkings.id');
        $now = now();

        $entries = ParkingSession::with(['parking.rates', 'agent'])
            ->whereIn('parking_id', $parkingIds)
            ->where('status', 'occupied')
            ->latest('started_at')
            ->get()
            ->map(function ($s) use ($now) {
                // Cherche un tarif correspondant à l'heure actuelle
                $timeStr = $now->format('H:i');
                $rate = null;
                if ($s->parking) {
                    $rate = $s->parking->rates
                        ->first(fn($r) =>
                            $r->from_time !== null && $r->to_time !== null &&
                            $timeStr >= $r->from_time && $timeStr <= $r->to_time
                        );
                }
                // Montant horaire : tarif trouvé → sinon prix de base du parking
                $pricePerHour = $rate
                    ? (float) $rate->amount
                    : ($s->parking?->price !== null ? (float) $s->parking->price : null);

                return [
                    'id'              => $s->id,
                    'license_plate'   => $s->license_plate,
                    'marque'          => $s->marque,
                    'modele'          => $s->modele,
                    'parking_name'    => $s->parking?->name,
                    'started_at'      => $s->started_at->toDateTimeString(),
                    'status'          => $s->status,
                    'created_by_name' => $s->agent?->name,
                    'price_per_hour'  => $pricePerHour,
                ];
            });

        return response()->json($entries);
    }
    /**
     * Historique des sorties que ce caissier a clôturées (peu importe qui a fait l'entrée).
     */
    public function historyExits(Request $request)
    {
        $user = auth()->user();

        $exits = ParkingSession::with(['parking', 'agent'])
            ->where('status', 'released')
            ->where('closed_by', $user->id)
            ->latest('ended_at')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'parking_name'     => $s->parking?->name,
                'started_at'       => $s->started_at->toDateTimeString(),
                'ended_at'         => $s->ended_at->toDateTimeString(),
                'duration_minutes' => $s->duration_minutes,
                'amount'           => $s->amount,
                'payment_method'   => $s->payment_method,
                'created_by_name'  => $s->agent?->name,
            ]);

        return response()->json($exits);
    }

    /**
     * Obtenir les statistiques financières du caissier.
     */
    public function stats()
    {
        $user = auth()->user();

        // Base query : sessions clôturées par ce caissier
        $base = ParkingSession::where('closed_by', $user->id)
            ->where('status', 'released');

        // Total encaissé (total collecté par le caissier)
        $totalEncaisse = (clone $base)->sum('amount');

        // Total versé (par le caissier aux admins/superviseurs)
        $totalVerse = Versement::where('caissier_id', $user->id)->sum('paid_amount');

        // Dette restante = sessions non encore collectées dans un versement + dette du dernier versement
        $pendingAmount = (clone $base)
            ->whereNull('versement_id')
            ->whereNull('reversement_id')
            ->sum('amount');

        $previousDebt = Versement::where('caissier_id', $user->id)
            ->latest()
            ->value('remaining_debt') ?? 0;

        $detteRestante = max(0, $pendingAmount + $previousDebt);

        return response()->json([
            'total_encaisse' => round($totalEncaisse, 2),
            'total_verse'    => round($totalVerse, 2),
            'dette_restante' => round($detteRestante, 2),
        ]);
    }

    /**
     * Obtenir le rapport filtré des sessions pour ce caissier (filtré optionnellement par agent).
     */
    public function reports(Request $request)
    {
        $user = auth()->user();
        $parkingIds = \Illuminate\Support\Facades\DB::table('user_parkings')
            ->where('user_id', $user->id)
            ->pluck('parking_id')
            ->toArray();

        // Agents affectés aux parkings du caissier
        $agents = User::where('role', User::ROLE_ATTENDANT)
            ->where(function ($q) use ($parkingIds) {
                $q->whereHas('parkings', fn ($pq) => $pq->whereIn('parkings.id', $parkingIds))
                  ->orWhereHas('closedSessions', fn ($sq) => $sq->whereIn('parking_id', $parkingIds));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'email', 'unique_code']);

        [$dateFrom, $dateTo] = $this->resolveReportDates($request);
        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $reportData = $this->queryReportData($parkingIds, $agentId, $dateFrom, $dateTo);

        return response()->json([
            'agents'   => $agents,
            'sessions' => $reportData['sessions'],
            'stats'    => [
                'total_entries'     => $reportData['totalEntries'],
                'total_exits'       => $reportData['totalExits'],
                'total_montant'     => round($reportData['totalMontant'], 2),
                'total_sessions'    => count($reportData['sessions']),
                'total_reverse'     => round($reportData['totalReverse'], 2),
                'total_non_reverse' => round($reportData['totalNonReverse'], 2),
            ],
            'filters' => [
                'periode'   => $request->input('periode', 'jour'),
                'date_from' => $dateFrom->toDateString(),
                'date_to'   => $dateTo->toDateString(),
                'agent_id'  => $agentId ? (string) $agentId : '',
            ],
        ]);
    }

    private function resolveReportDates(Request $request): array
    {
        $periode = $request->input('periode', 'jour');

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $from = Carbon::parse($request->input('date_from'))->startOfDay();
            $to   = Carbon::parse($request->input('date_to'))->endOfDay();
            return [$from, $to];
        }

        $now = Carbon::now();

        return match ($periode) {
            'mois'  => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'annee' => [$now->copy()->startOfYear(),  $now->copy()->endOfYear()],
            default => [$now->copy()->startOfDay(),   $now->copy()->endOfDay()],   // jour
        };
    }

    private function queryReportData(array $parkingIds, ?int $agentId, Carbon $from, Carbon $to): array
    {
        // 1. Entrées enregistrées
        $entriesQuery = ParkingSession::whereIn('parking_id', $parkingIds)
            ->whereBetween('started_at', [$from, $to]);
        if ($agentId) {
            $entriesQuery->where('created_by', $agentId);
        }
        $totalEntries = $entriesQuery->count();

        // 2. Sorties effectuées
        $exitsQuery = ParkingSession::whereIn('parking_id', $parkingIds)
            ->where('status', 'released')
            ->whereBetween('ended_at', [$from, $to]);
        if ($agentId) {
            $exitsQuery->where(function ($q) use ($agentId) {
                $q->where('closed_by', $agentId)
                  ->orWhere('created_by', $agentId);
            });
        }
        $totalExits = $exitsQuery->count();

        // 3. Sessions
        $sessionsQuery = ParkingSession::with(['parking', 'versement', 'agent', 'closer'])
            ->whereIn('parking_id', $parkingIds)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('started_at', [$from, $to])
                  ->orWhereBetween('ended_at', [$from, $to]);
            });

        if ($agentId) {
            $sessionsQuery->where(function ($q) use ($agentId) {
                $q->where('created_by', $agentId)
                  ->orWhere('closed_by', $agentId);
            });
        }

        $rawSessions = $sessionsQuery->orderByDesc('started_at')->get();

        $totalMontant = $rawSessions->where('status', 'released')->sum('amount');
        $totalReverse = $rawSessions->where('status', 'released')->whereNotNull('versement_id')->sum('amount');
        $totalNonReverse = max(0, $totalMontant - $totalReverse);

        $sessions = $rawSessions->map(fn($s) => [
            'id'               => $s->id,
            'license_plate'    => $s->license_plate,
            'marque'           => $s->marque,
            'modele'           => $s->modele,
            'started_at'       => $s->started_at?->format('Y-m-d H:i:s'),
            'ended_at'         => $s->ended_at?->format('Y-m-d H:i:s'),
            'duration_minutes' => $s->duration_minutes ?? null,
            'amount'           => (float) ($s->amount ?? 0.0),
            'versement_id'     => $s->versement_id,
            'parking_name'     => $s->parking?->name ?? '—',
            'agent_entry_name' => $s->agent ? trim($s->agent->name . ' ' . ($s->agent->first_name ?? '')) : '—',
            'agent_exit_name'  => $s->closer ? trim($s->closer->name . ' ' . ($s->closer->first_name ?? '')) : '—',
            'status'           => $s->status,
            'created_at'       => $s->created_at?->format('Y-m-d H:i:s'),
        ])->values();

        return [
            'sessions'        => $sessions,
            'totalEntries'    => $totalEntries,
            'totalExits'      => $totalExits,
            'totalMontant'    => $totalMontant,
            'totalReverse'    => $totalReverse,
            'totalNonReverse' => $totalNonReverse,
        ];
    }

    /**
     * Export PDF pour l'API
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        $parkingIds = \Illuminate\Support\Facades\DB::table('user_parkings')
            ->where('user_id', $user->id)
            ->pluck('parking_id')
            ->toArray();

        [$dateFrom, $dateTo] = $this->resolveReportDates($request);
        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $selectedAgent = $agentId ? User::find($agentId) : null;
        $agentLabel = $selectedAgent
            ? trim($selectedAgent->name . ' ' . ($selectedAgent->first_name ?? ''))
            : 'Tous les agents';

        $reportData = $this->queryReportData($parkingIds, $agentId, $dateFrom, $dateTo);

        $data = [
            'sessions'        => $reportData['sessions'],
            'totalEntries'    => $reportData['totalEntries'],
            'totalExits'      => $reportData['totalExits'],
            'totalMontant'    => $reportData['totalMontant'],
            'totalReverse'    => $reportData['totalReverse'],
            'totalNonReverse' => $reportData['totalNonReverse'],
            'caissier'        => trim($user->name . ' ' . ($user->first_name ?? '')),
            'agentLabel'      => $agentLabel,
            'dateFrom'        => $dateFrom->format('d/m/Y'),
            'dateTo'          => $dateTo->format('d/m/Y'),
            'generatedAt'     => now()->format('d/m/Y H:i'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('caissier.rapport-pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("rapport-caissier-{$dateFrom->format('Ymd')}-{$dateTo->format('Ymd')}.pdf");
    }

    /**
     * Export CSV pour l'API
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        $parkingIds = \Illuminate\Support\Facades\DB::table('user_parkings')
            ->where('user_id', $user->id)
            ->pluck('parking_id')
            ->toArray();

        [$dateFrom, $dateTo] = $this->resolveReportDates($request);
        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $reportData = $this->queryReportData($parkingIds, $agentId, $dateFrom, $dateTo);
        $sessions   = $reportData['sessions'];

        $filename = "rapport-caissier-{$dateFrom->format('Ymd')}-{$dateTo->format('Ymd')}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($sessions) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Plaque',
                'Marque',
                'Modèle',
                'Parking',
                'Agent Entrée',
                'Agent/Caissier Sortie',
                'Entrée',
                'Sortie',
                'Durée (min)',
                'Montant (FCFA)',
                'Statut',
                'Reversé',
            ], ';');

            foreach ($sessions as $s) {
                fputcsv($handle, [
                    $s['license_plate'],
                    $s['marque']    ?? '',
                    $s['modele']    ?? '',
                    $s['parking_name'] ?? '—',
                    $s['agent_entry_name'] ?? '—',
                    $s['agent_exit_name'] ?? '—',
                    $s['started_at'] ?? '',
                    $s['ended_at']   ?? '',
                    $s['duration_minutes'] ?? '',
                    $s['amount'],
                    $s['status'] === 'occupied' ? 'En cours' : 'Clôturé',
                    $s['versement_id'] ? 'Oui' : 'Non',
                ], ';');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
