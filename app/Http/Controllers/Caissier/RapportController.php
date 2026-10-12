<?php

namespace App\Http\Controllers\Caissier;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class RapportController extends Controller
{
    /**
     * Affiche la page rapport avec les sessions et statistiques filtrées par agent et période.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $parkingIds = $user->parkings()->pluck('parkings.id')->toArray();

        // Récupérer la liste des agents (attendants) assignés aux parkings du caissier
        $agents = User::where('role', User::ROLE_ATTENDANT)
            ->where(function ($q) use ($parkingIds) {
                $q->whereHas('parkings', fn ($pq) => $pq->whereIn('parkings.id', $parkingIds))
                  ->orWhereHas('closedSessions', fn ($sq) => $sq->whereIn('parking_id', $parkingIds));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'email', 'unique_code']);

        [$dateFrom, $dateTo] = $this->resolveDates($request);
        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $reportData = $this->buildReportData($parkingIds, $agentId, $dateFrom, $dateTo);

        return Inertia::render('Caissier/Rapport/Index', [
            'agents'          => $agents,
            'sessions'        => $reportData['sessions'],
            'totalEntries'    => $reportData['totalEntries'],
            'totalExits'      => $reportData['totalExits'],
            'totalMontant'    => $reportData['totalMontant'],
            'totalReverse'    => $reportData['totalReverse'],
            'totalNonReverse' => $reportData['totalNonReverse'],
            'filters'         => [
                'periode'   => $request->input('periode', 'jour'),
                'date_from' => $dateFrom->toDateString(),
                'date_to'   => $dateTo->toDateString(),
                'agent_id'  => $agentId ? (string) $agentId : '',
            ],
        ]);
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $parkingIds = $user->parkings()->pluck('parkings.id')->toArray();

        [$dateFrom, $dateTo] = $this->resolveDates($request);
        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $selectedAgent = $agentId ? User::find($agentId) : null;
        $agentLabel = $selectedAgent
            ? trim($selectedAgent->name . ' ' . ($selectedAgent->first_name ?? ''))
            : 'Tous les agents';

        $reportData = $this->buildReportData($parkingIds, $agentId, $dateFrom, $dateTo);

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

        $pdf = Pdf::loadView('caissier.rapport-pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("rapport-caissier-{$dateFrom->format('Ymd')}-{$dateTo->format('Ymd')}.pdf");
    }

    /**
     * Export CSV (Excel compatible)
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        $parkingIds = $user->parkings()->pluck('parkings.id')->toArray();

        [$dateFrom, $dateTo] = $this->resolveDates($request);
        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $reportData = $this->buildReportData($parkingIds, $agentId, $dateFrom, $dateTo);
        $sessions = $reportData['sessions'];

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
                    $s['marque'] ?? '',
                    $s['modele'] ?? '',
                    $s['parking'] ?? '—',
                    $s['agent_entry'] ?? '—',
                    $s['agent_exit'] ?? '—',
                    $s['started_at'] ?? '',
                    $s['ended_at'] ?? '',
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

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveDates(Request $request): array
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

    private function buildReportData(array $parkingIds, ?int $agentId, Carbon $from, Carbon $to): array
    {
        // 1. Nombre de stationnements enregistrés (Entrées créées dans la période)
        $entriesQuery = ParkingSession::whereIn('parking_id', $parkingIds)
            ->whereBetween('started_at', [$from, $to]);
        if ($agentId) {
            $entriesQuery->where('created_by', $agentId);
        }
        $totalEntries = $entriesQuery->count();

        // 2. Nombre de sorties effectuées (Sessions libérées dans la période)
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

        // 3. Sessions à afficher dans le tableau
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

        $sessions = $rawSessions->map(function (ParkingSession $s) {
            $agentEntryName = $s->agent ? trim($s->agent->name . ' ' . ($s->agent->first_name ?? '')) : '—';
            $agentExitName  = $s->closer ? trim($s->closer->name . ' ' . ($s->closer->first_name ?? '')) : '—';

            return [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'started_at'       => $s->started_at?->format('d/m/Y H:i'),
                'ended_at'         => $s->ended_at?->format('d/m/Y H:i'),
                'duration_minutes' => $s->duration_minutes ?? null,
                'amount'           => (float) $s->amount,
                'status'           => $s->status,
                'versement_id'     => $s->versement_id,
                'parking'          => $s->parking?->name ?? '—',
                'agent_entry'      => $agentEntryName,
                'agent_exit'       => $agentExitName,
            ];
        })->values();

        return [
            'sessions'        => $sessions,
            'totalEntries'    => $totalEntries,
            'totalExits'      => $totalExits,
            'totalMontant'    => $totalMontant,
            'totalReverse'    => $totalReverse,
            'totalNonReverse' => $totalNonReverse,
        ];
    }
}
