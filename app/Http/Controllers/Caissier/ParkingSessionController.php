<?php

namespace App\Http\Controllers\Caissier;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\ParkingRate;
use App\Models\ParkingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ParkingSessionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $parkingIds = $user->parkings()->pluck('parkings.id');

        $sessions = ParkingSession::with('parking', 'agent')
            ->whereIn('parking_id', $parkingIds)
            ->where('status', 'occupied')
            ->latest('started_at')
            ->get();

        return Inertia::render('Caissier/ParkingSessions/Index', [
            'sessions' => $sessions,
        ]);
    }

    public function create()
    {
        return redirect()->route('caissier.parking-sessions.index')
            ->with('error', 'L\'enregistrement des entrées de véhicules est exclusivement réservé aux agents de parking.');
    }

    public function store(Request $request)
    {
        return redirect()->route('caissier.parking-sessions.index')
            ->with('error', 'L\'enregistrement des entrées de véhicules est exclusivement réservé aux agents de parking.');
    }

    /**
     * Page d'enregistrement des sorties : liste toutes les sessions actives (filtrée optionnellement par immatriculation).
     */
    public function checkout(Request $request)
    {
        $plate = strtoupper(trim($request->get('plate', '')));

        $query = ParkingSession::with('parking')
            ->where('status', 'occupied')
            ->latest('started_at');

        if ($plate !== '') {
            $query->where('license_plate', 'like', "%{$plate}%");
        }

        $sessions = $query->get()->map(function ($s) {
            $minutes = $s->duration_minutes;
            $amount  = ParkingRate::calculateAmount(now()->format('H:i'), $s->parking_id);

            return [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'parking_name'     => $s->parking?->name,
                'parking_id'       => $s->parking_id,
                'parking_is_active'=> (bool) $s->parking?->is_active,
                'parking_price'    => $s->parking?->price,
                'started_at'       => $s->started_at,
                'duration_minutes' => $minutes,
                'amount'           => $amount,
            ];
        });

        $rates = ParkingRate::select('parking_id', 'from_time', 'to_time', 'amount')->get();

        return Inertia::render('Caissier/ParkingSessions/Checkout', [
            'plate'    => $plate,
            'sessions' => $sessions,
            'rates'    => $rates,
        ]);
    }

    /**
     * Enregistre la sortie d'un véhicule.
     */
    public function checkoutStore(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:parking_sessions,id',
        ]);

        $session = ParkingSession::where('id', $request->session_id)
            ->where('status', 'occupied')
            ->firstOrFail();

        $session->load('parking');

        if (! $session->parking?->is_active) {
            Log::warning('Tentative de sortie sur un parking bloqué (caissier)', [
                'session_id'   => $session->id,
                'parking_id'   => $session->parking_id,
                'caissier_id'  => auth()->id(),
            ]);

            return back()->withErrors(['session_id' => 'Ce parking est actuellement bloqué. Aucune action n\'est autorisée.']);
        }

        $endedAt = now();
        $minutes = (int) ceil($session->started_at->diffInMinutes($endedAt));
        $amount  = ParkingRate::calculateAmount($endedAt->format('H:i'), $session->parking_id);

        $session->update([
            'status'    => 'released',
            'ended_at'  => $endedAt,
            'amount'    => $amount,
            'closed_by' => auth()->id(),
        ]);

        Log::info('Sortie enregistrée (caissier)', [
            'session_id'    => $session->id,
            'license_plate' => $session->license_plate,
            'minutes'       => $minutes,
            'amount'        => $amount,
            'caissier_id'   => auth()->id(),
        ]);

        return redirect()
            ->route('caissier.checkout')
            ->with('checked_out_session_id', $session->id);
    }

    /**
     * Affiche le ticket d'entrée pour une session.
     */
    public function ticket(ParkingSession $session)
    {
        $session->load('parking', 'agent');

        return Inertia::render('Parking/Ticket', [
            'session' => [
                'id'            => $session->id,
                'license_plate' => $session->license_plate,
                'marque'        => $session->marque,
                'modele'        => $session->modele,
                'parking_name'  => $session->parking?->name,
                'parking_ref'   => $session->parking?->reference,
                'started_at'    => $session->started_at,
                'agent_name'    => $session->agent?->name,
            ],
            'role' => 'caissier',
        ]);
    }

    /**
     * Affiche le reçu de sortie pour une session.
     */
    public function receipt(ParkingSession $session)
    {
        $session->load('parking', 'agent', 'closer');
        $minutes = (int) ceil($session->started_at->diffInMinutes($session->ended_at));

        return Inertia::render('Parking/Receipt', [
            'session' => [
                'id'               => $session->id,
                'license_plate'    => $session->license_plate,
                'marque'           => $session->marque,
                'modele'           => $session->modele,
                'parking_name'     => $session->parking?->name,
                'parking_ref'      => $session->parking?->reference,
                'started_at'       => $session->started_at,
                'ended_at'         => $session->ended_at,
                'duration_minutes' => $minutes,
                'amount'           => $session->amount,
                'agent_name'       => $session->agent?->name,
                'closer_name'      => $session->closer?->name,
            ],
            'role' => 'caissier',
        ]);
    }

    /**
     * Historique des entrées et sorties enregistrées par ce caissier.
     */
    public function history()
    {
        $userId = auth()->id();

        $entries = ParkingSession::with('parking')
            ->where('created_by', $userId)
            ->latest('started_at')
            ->get()
            ->map(function ($s) {
                return [
                    'id'            => $s->id,
                    'license_plate' => $s->license_plate,
                    'marque'        => $s->marque,
                    'modele'        => $s->modele,
                    'parking_name'  => $s->parking?->name,
                    'started_at'    => $s->started_at,
                    'status'        => $s->status,
                ];
            });

        $exits = ParkingSession::with('parking')
            ->where('status', 'released')
            ->where('closed_by', $userId)
            ->latest('ended_at')
            ->get()
            ->map(function ($s) {
                return [
                    'id'               => $s->id,
                    'license_plate'    => $s->license_plate,
                    'marque'           => $s->marque,
                    'modele'           => $s->modele,
                    'parking_name'     => $s->parking?->name,
                    'started_at'       => $s->started_at,
                    'ended_at'         => $s->ended_at,
                    'duration_minutes' => (int) ceil($s->started_at->diffInMinutes($s->ended_at)),
                    'amount'           => $s->amount,
                ];
            });

        return Inertia::render('Caissier/ParkingSessions/History', [
            'entries' => $entries,
            'exits'   => $exits,
        ]);
    }
}
