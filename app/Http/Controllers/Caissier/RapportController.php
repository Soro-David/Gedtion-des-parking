<?php

namespace App\Http\Controllers\Caissier;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class RapportController extends Controller
{
    /**
     * Affiche la page rapport avec les sessions filtrées.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        [$dateFrom, $dateTo] = $this->resolveDates($request);

        $sessions = $this->querySessions($user->id, $dateFrom, $dateTo);

        $totalMontant   = $sessions->sum('amount');
        $totalSessions  = $sessions->count();
        $totalReversé   = $sessions->whereNotNull('reversement_id')->sum('amount');
        $totalNonReversé = $sessions->whereNull('reversement_id')->sum('amount');

        return Inertia::render('Caissier/Rapport/Index', [
            'sessions'        => $sessions->values(),
            'totalMontant'    => $totalMontant,
            'totalSessions'   => $totalSessions,
            'totalReverse'    => $totalReversé,
            'totalNonReverse' => $totalNonReversé,
            'filters'         => [
                'periode'   => $request->input('periode', 'jour'),
                'date_from' => $dateFrom->toDateString(),
                'date_to'   => $dateTo->toDateString(),
            ],
        ]);
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        [$dateFrom, $dateTo] = $this->resolveDates($request);
        $sessions = $this->querySessions($user->id, $dateFrom, $dateTo);

        $data = [
            'sessions'        => $sessions,
            'totalMontant'    => $sessions->sum('amount'),
            'totalSessions'   => $sessions->count(),
            'totalReverse'    => $sessions->whereNotNull('reversement_id')->sum('amount'),
            'totalNonReverse' => $sessions->whereNull('reversement_id')->sum('amount'),
            'caissier'        => $user->name . ' ' . ($user->first_name ?? ''),
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
        [$dateFrom, $dateTo] = $this->resolveDates($request);
        $sessions = $this->querySessions($user->id, $dateFrom, $dateTo);

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

    private function querySessions(int $userId, Carbon $from, Carbon $to)
    {
        return ParkingSession::with('parking')
            ->where('closed_by', $userId)
            ->where('status', 'released')
            ->whereBetween('ended_at', [$from, $to])
            ->orderByDesc('ended_at')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'started_at'       => $s->started_at?->format('d/m/Y H:i'),
                'ended_at'         => $s->ended_at?->format('d/m/Y H:i'),
                'duration_minutes' => $s->duration_minutes ?? null,
                'amount'           => $s->amount,
                'reversement_id'   => $s->reversement_id,
                'parking'          => $s->parking?->name ?? '—',
            ]);
    }
}
