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
        $sessions = ParkingSession::with('parking', 'agent')
            ->where('created_by', auth()->id())
            ->where('status', '!=', 'released')
            ->latest()
            ->get();

            // dd($sessions);
        return Inertia::render('Caissier/ParkingSessions/Index', [
            'sessions' => $sessions,
        ]);
    }

    public function create()
    {
        $parkings = auth()->user()->parkings()
            ->where('parkings.is_active', true)
            ->get(['parkings.id', 'parkings.name', 'parkings.reference', 'parkings.capacity'])
            ->map(function ($parking) {
                return [
                    'id'              => $parking->id,
                    'name'            => $parking->name,
                    'reference'       => $parking->reference,
                    'capacity'        => $parking->capacity,
                    'available_spots' => $parking->available_spots,
                ];
            });

        return Inertia::render('Caissier/ParkingSessions/Create', [
            'parkings' => $parkings,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'parking_id'    => 'required|exists:parkings,id',
            'license_plate' => 'required|string|max:20',
            'marque'        => 'nullable|string|max:100',
            'modele'        => 'nullable|string|max:100',
            'vehicle_image' => 'nullable|image|max:5120',
        ]);

        // Vérifier que le parking appartient bien aux parkings affectés au caissier
        $assignedIds = auth()->user()->parkings()->pluck('parkings.id')->toArray();
        if (! in_array((int) $request->parking_id, $assignedIds)) {
            Log::warning('Accès non autorisé au parking (caissier)', [
                'parking_id' => $request->parking_id,
                'caissier_id' => auth()->id(),
            ]);
            return back()->withErrors(['parking_id' => 'Ce parking ne vous est pas affecté.']);
        }

        $parking = Parking::findOrFail($request->parking_id);

        if ($parking->available_spots <= 0) {
            Log::warning('Tentative de stationnement sur parking complet (caissier)', [
                'parking_id'  => $parking->id,
                'caissier_id' => auth()->id(),
            ]);
            return back()->withErrors(['parking_id' => 'Ce parking n\'a plus de places disponibles.']);
        }

        $imagePath = null;
        if ($request->hasFile('vehicle_image')) {
            $imagePath = $request->file('vehicle_image')->store('vehicle-images', 'public');
        }

        $session = ParkingSession::create([
            'parking_id'    => $parking->id,
            'license_plate' => strtoupper($request->license_plate),
            'marque'        => $request->marque,
            'modele'        => $request->modele,
            'vehicle_image' => $imagePath,
            'status'        => 'occupied',
            'started_at'    => now(),
            'created_by'    => auth()->id(),
        ]);

        Log::info('Session de stationnement enregistrée (caissier)', [
            'parking_id'    => $parking->id,
            'license_plate' => strtoupper($request->license_plate),
            'caissier_id'   => auth()->id(),
        ]);

        return redirect()->route('caissier.parking-sessions.ticket', $session->id);
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
            $amount  = ParkingRate::calculateAmount($minutes, $s->parking_id);

            return [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'parking_name'     => $s->parking?->name,
                'parking_id'       => $s->parking_id,
                'started_at'       => $s->started_at,
                'duration_minutes' => $minutes,
                'amount'           => $amount,
            ];
        });

        $rates = ParkingRate::select('parking_id', 'from_minutes', 'to_minutes', 'amount')->get();

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

        $endedAt = now();
        $minutes = (int) ceil($session->started_at->diffInMinutes($endedAt));
        $amount  = ParkingRate::calculateAmount($minutes, $session->parking_id);

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

        return redirect()->route('caissier.parking-sessions.receipt', $session->id);
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
     * Historique des sorties encaissées par ce caissier.
     */
    public function history()
    {
        $sessions = ParkingSession::with('parking')
            ->where('status', 'released')
            ->where('closed_by', auth()->id())
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
            'sessions' => $sessions,
        ]);
    }
}
