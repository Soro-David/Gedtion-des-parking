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
     */
    public function store(Request $request)
    {
        $request->validate([
            'parking_id'    => 'required|exists:parkings,id',
            'license_plate' => 'required|string|max:20',
            'phone'         => 'nullable|string|max:20',
            'marque'        => 'nullable|string|max:100',
            'modele'        => 'nullable|string|max:100',
            'vehicle_image' => 'nullable|image|max:5120',
        ]);

        $user = auth()->user();

        // Vérifier que le parking est affecté au caissier
        $assignedIds = $user->parkings()->pluck('parkings.id')->toArray();
        if (!in_array((int)$request->parking_id, $assignedIds)) {
            Log::warning('API Caissier - Accès non autorisé au parking', [
                'parking_id' => $request->parking_id,
                'caissier_id' => $user->id,
            ]);
            return response()->json([
                'message' => 'Ce parking ne vous est pas affecté.'
            ], 403);
        }

        $parking = Parking::findOrFail($request->parking_id);

        if ($parking->available_spots <= 0) {
            Log::warning('API Caissier - Tentative de stationnement sur parking complet', [
                'parking_id'  => $parking->id,
                'caissier_id' => $user->id,
            ]);
            return response()->json([
                'message' => "Ce parking n'a plus de places disponibles."
            ], 422);
        }

        $normalizedPlate = ParkingSession::normalizeLicensePlate($request->license_plate);

        if (ParkingSession::hasActiveForLicensePlate($normalizedPlate)) {
            return response()->json([
                'message' => 'Ce véhicule a déjà un stationnement actif.'
            ], 422);
        }

        $imagePath = null;
        if ($request->hasFile('vehicle_image')) {
            $imagePath = $request->file('vehicle_image')->store('vehicle-images', 'public');
        }

        $session = ParkingSession::create([
            'parking_id'    => $parking->id,
            'license_plate' => $normalizedPlate,
            'phone'         => $request->phone,
            'marque'        => $request->marque,
            'modele'        => $request->modele,
            'vehicle_image' => $imagePath ? asset('storage/' . $imagePath) : null,
            'status'        => 'occupied',
            'started_at'    => now(),
            'created_by'    => $user->id,
        ]);

        Log::info('API Caissier - Session de stationnement enregistrée', [
            'parking_id'    => $parking->id,
            'license_plate' => $session->license_plate,
            'caissier_id'   => $user->id,
        ]);

        // ✓ Notification FCM : informer tous les agents et caissiers du même parking
        try {
            $recipients = $parking->users()
                ->whereIn('role', ['attendant', 'caissier'])
                ->where('users.id', '!=', $user->id)
                ->pluck('users.id')
                ->toArray();

            if (!empty($recipients)) {
                app(FcmNotificationService::class)->sendToUsers(
                    $recipients,
                    'Nouveau véhicule enregistré',
                    "Un véhicule vient d'être enregistré dans le parking {$parking->name} par {$user->name}.",
                    [
                        'type'       => 'entree',
                        'id'         => (string) $session->id,
                        'parking_id' => (string) $parking->id,
                    ],
                    'entree'
                );
            }
        } catch (\Exception $e) {
            Log::warning('[FCM Caissier] Erreur notification entrée collègues', ['error' => $e->getMessage()]);
        }

        return response()->json([

            'message' => 'Stationnement enregistré avec succès.',
            'session' => [
                'id'            => $session->id,
                'parking_id'    => $session->parking_id,
                'parking_name'  => $parking->name,
                'parking_ref'   => $parking->reference,
                'license_plate' => $session->license_plate,
                'phone'         => $session->phone,
                'marque'        => $session->marque,
                'modele'        => $session->modele,
                'vehicle_image' => $session->vehicle_image,
                'status'        => $session->status,
                'started_at'    => $session->started_at->toDateTimeString(),
            ]
        ], 201);
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
     * Obtenir le rapport filtré des sessions clôturées par ce caissier.
     */
    public function reports(Request $request)
    {
        $user = auth()->user();

        [$dateFrom, $dateTo] = $this->resolveReportDates($request);

        $sessions = $this->queryReportSessions($user->id, $dateFrom, $dateTo);

        $totalMontant   = $sessions->sum('amount');
        $totalSessions  = $sessions->count();
        $versementIds   = $sessions->pluck('versement_id')->filter()->unique();
        $totalReverse   = \App\Models\Versement::whereIn('id', $versementIds)->sum('paid_amount');
        $totalNonReverse = max(0, $totalMontant - $totalReverse);

        return response()->json([
            'sessions' => $sessions->values(),
            'stats' => [
                'total_montant'     => round($totalMontant, 2),
                'total_sessions'    => $totalSessions,
                'total_reverse'     => round($totalReverse, 2),
                'total_non_reverse' => round($totalNonReverse, 2),
            ],
            'filters' => [
                'periode'   => $request->input('periode', 'jour'),
                'date_from' => $dateFrom->toDateString(),
                'date_to'   => $dateTo->toDateString(),
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

    private function queryReportSessions(int $userId, Carbon $from, Carbon $to)
    {
        $parkingIds = \Illuminate\Support\Facades\DB::table('user_parkings')
            ->where('user_id', $userId)
            ->pluck('parking_id')
            ->toArray();

        return ParkingSession::with(['parking', 'versement', 'agent', 'closer'])
            ->where(function ($q) use ($userId, $from, $to, $parkingIds) {
                // Sessions clôturées par ce caissier dans la période
                $q->where(function ($sq) use ($userId, $from, $to) {
                    $sq->where('closed_by', $userId)
                       ->where('status', 'released')
                       ->where(function ($vq) use ($from, $to) {
                           $vq->whereHas('versement', function ($inner) use ($from, $to) {
                               $inner->whereBetween('created_at', [$from, $to]);
                           })
                           ->orWhere(function ($inner) use ($from, $to) {
                               $inner->whereNull('versement_id')
                                     ->whereBetween('ended_at', [$from, $to]);
                           });
                       });
                })
                // OU Sessions en cours dans la période, pour les parkings affectés à ce caissier
                ->orWhere(function ($sq) use ($from, $to, $parkingIds) {
                    $sq->where('status', 'occupied')
                       ->whereBetween('started_at', [$from, $to])
                       ->whereIn('parking_id', $parkingIds);
                });
            })
            ->orderByDesc('started_at')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'started_at'       => $s->started_at?->format('Y-m-d H:i:s'),
                'ended_at'         => $s->ended_at?->format('Y-m-d H:i:s'),
                'duration_minutes' => $s->duration_minutes ?? null,
                'amount'           => $s->amount ?? 0.0,
                'versement_id'     => $s->versement_id,
                'parking_name'     => $s->parking?->name ?? '—',
                'agent_entry_name' => $s->agent ? trim($s->agent->name . ' ' . ($s->agent->first_name ?? '')) : '—',
                'agent_exit_name'  => $s->closer ? trim($s->closer->name . ' ' . ($s->closer->first_name ?? '')) : '—',
                'status'           => $s->status,
                'created_at'       => $s->created_at?->format('Y-m-d H:i:s'),
            ]);
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

        [$dateFrom, $dateTo] = $this->resolveReportDates($request);
        $sessions = $this->queryReportSessions($user->id, $dateFrom, $dateTo);

        $totalMontant = $sessions->sum('amount');
        $versementIds = $sessions->pluck('versement_id')->filter()->unique();
        $totalReverse = \App\Models\Versement::whereIn('id', $versementIds)->sum('paid_amount');
        $totalNonReverse = max(0, $totalMontant - $totalReverse);

        $data = [
            'sessions'        => $sessions,
            'totalMontant'    => $totalMontant,
            'totalSessions'   => $sessions->count(),
            'totalReverse'    => $totalReverse,
            'totalNonReverse' => $totalNonReverse,
            'caissier'        => $user->name . ' ' . ($user->first_name ?? ''),
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

        [$dateFrom, $dateTo] = $this->resolveReportDates($request);
        $sessions = $this->queryReportSessions($user->id, $dateFrom, $dateTo);

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
                'Entrée',
                'Sortie',
                'Durée (min)',
                'Montant (FCFA)',
                'Reversé',
            ], ';');

            foreach ($sessions as $s) {
                fputcsv($handle, [
                    $s['license_plate'],
                    $s['marque']    ?? '',
                    $s['modele']    ?? '',
                    $s['started_at'] ?? '',
                    $s['ended_at']   ?? '',
                    $s['duration_minutes'] ?? '',
                    $s['amount'],
                    $s['reversement_id'] ? 'Oui' : 'Non',
                ], ';');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
